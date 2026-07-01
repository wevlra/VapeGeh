use crate::error::PrinterError;
use async_trait::async_trait;
use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Deserialize, Serialize, PartialEq)]
#[serde(rename_all = "snake_case")]
pub enum ConnectionType {
    Bluetooth,
    Usb,
    Tcp,
}

#[derive(Debug, Clone, Serialize)]
pub struct PrinterInfo {
    pub name: String,
    pub address: String,
    pub connection_type: ConnectionType,
}

/// Transport-agnostic printer connection (pattern: DeviceConnection dari referensi).
#[async_trait]
pub trait PrinterConnection: Send + Sync {
    fn connection_type(&self) -> ConnectionType;
    fn display_name(&self) -> String;
    async fn write(&self, data: &[u8]) -> Result<(), PrinterError>;
}
