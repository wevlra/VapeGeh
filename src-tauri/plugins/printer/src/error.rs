use serde::Serialize;

#[derive(Debug, thiserror::Error)]
pub enum PrinterError {
    #[error("Serial port error: {0}")]
    SerialPort(String),
    #[error("IO error: {0}")]
    Io(#[from] std::io::Error),
    #[error("Printer not found: {0}")]
    NotFound(String),
    #[error("Connection failed: {0}")]
    Connection(String),
    #[error("Write failed: {0}")]
    Write(String),
    #[error("No printers available")]
    NoPrinters,
    #[error("USB printer not detected: {0}")]
    UsbNotDetected(String),
    #[error("Platform not supported")]
    UnsupportedPlatform,
}

impl Serialize for PrinterError {
    fn serialize<S>(&self, serializer: S) -> Result<S::Ok, S::Error>
    where
        S: serde::Serializer,
    {
        serializer.serialize_str(self.to_string().as_ref())
    }
}
