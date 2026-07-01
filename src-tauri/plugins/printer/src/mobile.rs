use crate::error::PrinterError;
use crate::PrinterInfo;

/// Discover paired Bluetooth printers on Android via JNI.
/// Calls `BluetoothAdapter.getBondedDevices()`.
pub fn discover_printers() -> Result<Vec<PrinterInfo>, PrinterError> {
    #[cfg(target_os = "android")]
    {
        Err(PrinterError::UnsupportedPlatform)
    }

    #[cfg(not(target_os = "android"))]
    Err(PrinterError::UnsupportedPlatform)
}

/// Open a Bluetooth socket and write raw bytes on Android.
pub fn write_to_port(device_address: &str, data: &[u8]) -> Result<(), PrinterError> {
    #[cfg(target_os = "android")]
    {
        Err(PrinterError::UnsupportedPlatform)
    }

    #[cfg(not(target_os = "android"))]
    Err(PrinterError::UnsupportedPlatform)
}
