fn main() {
    tauri_plugin::Builder::new(&["list_printers", "print_receipt"]).build();
}
