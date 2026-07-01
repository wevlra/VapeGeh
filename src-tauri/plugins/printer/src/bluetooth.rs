use crate::connection::{ConnectionType, PrinterInfo};
use crate::error::PrinterError;
use bluer::rfcomm;
use tokio::io::AsyncWriteExt;

/// Discover paired Bluetooth printers via BlueZ D-Bus.
pub async fn discover() -> Result<Vec<PrinterInfo>, PrinterError> {
    let session = bluer::Session::new()
        .await
        .map_err(|e| PrinterError::Connection(format!("BlueZ session: {e}")))?;

    let adapter_names = session
        .adapter_names()
        .await
        .map_err(|e| PrinterError::Connection(format!("Adapter list: {e}")))?;

    let mut printers = Vec::new();

    for adapter_name in &adapter_names {
        let adapter = session
            .adapter(adapter_name)
            .map_err(|e| PrinterError::Connection(format!("Adapter '{adapter_name}': {e}")))?;

        let addresses = adapter
            .device_addresses()
            .await
            .map_err(|e| PrinterError::Connection(format!("Device list: {e}")))?;

        for addr in &addresses {
            let device = adapter
                .device(*addr)
                .map_err(|e| PrinterError::Connection(format!("Device create: {e}")))?;

            if !device.is_paired().await.unwrap_or(false) {
                continue;
            }

            let name = device.name().await.unwrap_or_default()
                .unwrap_or_else(|| addr.to_string());
            let mac = addr.to_string();

            let uuids = device.uuids().await.unwrap_or_default()
                .unwrap_or_default();
            let has_spp = uuids.iter().any(|u| {
                u.to_string().to_lowercase().starts_with("00001101")
            });

            let looks_printer = name.to_lowercase().contains("print")
                || name.to_lowercase().contains("pos")
                || name.to_lowercase().contains("receipt")
                || name.to_lowercase().contains("tp-");

            if has_spp || looks_printer || uuids.is_empty() {
                printers.push(PrinterInfo {
                    name: name.clone(),
                    address: mac,
                    connection_type: ConnectionType::Bluetooth,
                });
            }
        }
    }

    if printers.is_empty() {
        return Err(PrinterError::NoPrinters);
    }

    Ok(printers)
}

/// Write ESC/POS data to a Bluetooth printer via RFCOMM socket.
/// Pattern dari rfcomm_client.rs contoh bluer: Stream::connect langsung.
pub async fn write_to_device(mac_address: &str, data: &[u8]) -> Result<(), PrinterError> {
    let mac: bluer::Address = mac_address.parse()
        .map_err(|e| PrinterError::Connection(format!("Invalid MAC: {e}")))?;

    // Pastikan device terhubung via BlueZ
    let session = bluer::Session::new().await
        .map_err(|e| PrinterError::Connection(format!("BlueZ: {e}")))?;

    for name in &session.adapter_names().await.unwrap_or_default() {
        if let Ok(adapter) = session.adapter(name) {
            if let Ok(dev) = adapter.device(mac) {
                if !dev.is_connected().await.unwrap_or(false) {
                    let _ = dev.connect().await;
                }
            }
        }
    }

    // Channel scanning: coba channel 1-20, pake yang pertama connect
    // Pattern dari rfcomm_client.rs: Stream::connect(target_sa) langsung
    // tanpa ConnectProfile — ConnectProfile bikin koneksi duplikat.
    let mut last_err = PrinterError::Connection("No RFCOMM channel responded".into());

    for channel in 1..=20u8 {
        let sa = rfcomm::SocketAddr::new(mac, channel);
        match rfcomm::Stream::connect(sa).await {
            Ok(mut stream) => {
                stream.write_all(data).await
                    .map_err(|e| PrinterError::Write(format!("RFCOMM write ch{channel}: {e}")))?;
                return Ok(());
            }
            Err(e) => {
                last_err = PrinterError::Connection(format!("RFCOMM ch{channel}: {e}"));
                tokio::time::sleep(std::time::Duration::from_millis(200)).await;
            }
        }
    }

    Err(last_err)
}
