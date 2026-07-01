use crate::connection::{ConnectionType, PrinterInfo};
use crate::error::PrinterError;
use serialport::SerialPortType;
use std::io::Write;
use std::time::Duration;

/// Known thermal printer USB vendor/product IDs.
/// Pattern dari UsbPrintersConnections.java: filter USB_CLASS_PRINTER.
/// Di PC kita filter by VID/PID yang dikenal.
pub const THERMAL_PRINTER_VID_PIDS: &[(u16, &[u16])] = &[
    (0x0416, &[0x5011, 0x5111]), // Xprinter / Perixx XP-58, XP-80
    (0x0483, &[0x5740, 0x5720]), // STMicro CDC ACM
    (0x04b8, &[0x0202]),         // Epson TM-T20, TM-T88
    (0x0519, &[0x0001]),         // Star Micronics TSP100
    (0x1504, &[0x0006]),         // Bixolon SRP-350
];

fn is_thermal_printer(vid: u16, pid: u16) -> bool {
    THERMAL_PRINTER_VID_PIDS.iter().any(|(known_vid, pids)| {
        *known_vid == vid && pids.contains(&pid)
    })
}

/// Discover USB thermal printers via serialport-rs.
/// Pattern dari UsbPrintersConnections.getList().
pub fn discover() -> Result<Vec<PrinterInfo>, PrinterError> {
    let ports = serialport::available_ports()
        .map_err(|e| PrinterError::Connection(format!("Serial port scan: {e}")))?;

    let mut printers = Vec::new();

    for port in &ports {
        let name = match &port.port_type {
            SerialPortType::UsbPort(info) => {
                let vid = info.vid;
                let pid = info.pid;
                if !is_thermal_printer(vid, pid) {
                    continue;
                }
                info.product.as_deref()
                    .unwrap_or(&port.port_name)
                    .to_string()
            }
            _ => continue,
        };

        printers.push(PrinterInfo {
            name,
            address: port.port_name.clone(),
            connection_type: ConnectionType::Usb,
        });
    }

    if printers.is_empty() {
        return Err(PrinterError::UsbNotDetected(
            "Tidak ada printer USB terdeteksi. Pastikan printer terhubung.".into()
        ));
    }

    Ok(printers)
}

/// Write ESC/POS data to a USB serial printer.
pub fn write_to_port(path: &str, data: &[u8]) -> Result<(), PrinterError> {
    let mut port = serialport::new(path, 9600)
        .timeout(Duration::from_millis(5000))
        .open()
        .map_err(|e| PrinterError::Connection(format!("USB open {path}: {e}")))?;

    port.write_all(data)
        .map_err(|e| PrinterError::Write(format!("USB write {path}: {e}")))?;

    Ok(())
}
