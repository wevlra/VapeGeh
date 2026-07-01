mod commands;
mod connection;
mod error;
mod escpos;
mod bluetooth;
mod usb;
#[cfg(target_os = "android")]
mod mobile;

use tauri::{
    plugin::{Builder, TauriPlugin},
    Runtime,
};

pub use connection::PrinterInfo;

pub fn init<R: Runtime>() -> TauriPlugin<R> {
    Builder::new("printer")
        .invoke_handler(tauri::generate_handler![
            commands::list_printers,
            commands::print_receipt,
        ])
        .build()
}
