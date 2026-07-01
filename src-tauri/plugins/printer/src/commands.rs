use crate::connection::ConnectionType;
use crate::error::PrinterError;
use crate::escpos::{render_escpos, ReceiptPayload};
use crate::PrinterInfo;

#[tauri::command]
pub async fn list_printers() -> Result<Vec<PrinterInfo>, PrinterError> {
    #[cfg(not(target_os = "android"))]
    {
        let mut printers = Vec::new();

        // Bluetooth scan
        if let Ok(bt_printers) = crate::bluetooth::discover().await {
            printers.extend(bt_printers);
        }

        // USB scan
        if let Ok(usb_printers) = crate::usb::discover() {
            printers.extend(usb_printers);
        }

        if printers.is_empty() {
            return Err(PrinterError::NoPrinters);
        }

        Ok(printers)
    }

    #[cfg(target_os = "android")]
    {
        crate::mobile::discover_printers()
    }
}

#[tauri::command]
pub async fn print_receipt(
    address: String,
    connection_type: ConnectionType,
    receipt_data: ReceiptPayload,
) -> Result<(), PrinterError> {
    let escpos_bytes = render_escpos(&receipt_data);

    #[cfg(not(target_os = "android"))]
    {
        match connection_type {
            ConnectionType::Bluetooth => {
                crate::bluetooth::write_to_device(&address, &escpos_bytes).await?;
            }
            ConnectionType::Usb => {
                crate::usb::write_to_port(&address, &escpos_bytes)?;
            }
            ConnectionType::Tcp => {
                return Err(PrinterError::Connection(
                    "Koneksi TCP belum didukung.".into()
                ));
            }
        }
    }

    #[cfg(target_os = "android")]
    {
        crate::mobile::write_to_port(&address, &escpos_bytes)?;
    }

    Ok(())
}
