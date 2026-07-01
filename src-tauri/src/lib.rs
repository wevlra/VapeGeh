use tauri::Manager;

pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_printer::init())
        .setup(|app| {
            let handle = app.handle().clone();

            tauri::async_runtime::spawn(async move {
                // Poll Laravel until it responds (timeout: 30s)
                let client = reqwest::Client::builder()
                    .timeout(std::time::Duration::from_secs(2))
                    .build()
                    .unwrap_or_default();

                let deadline = std::time::Instant::now() + std::time::Duration::from_secs(30);

                loop {
                    match client
                        .get("https://vapegeh.wevlra.dev/")
                        .send()
                        .await
                    {
                        Ok(resp) if resp.status().is_success() => {
                            println!("[VapeGeh] Laravel is ready");
                            break;
                        }
                        Err(_) if std::time::Instant::now() >= deadline => {
                            println!("[VapeGeh] Laravel timeout — opening anyway");
                            break;
                        }
                        _ => {
                            tokio::time::sleep(std::time::Duration::from_millis(500)).await;
                        }
                    }
                }

                // Transition: close splash, show main
                if let Some(s) = handle.get_webview_window("splashscreen") {
                    let _ = s.close();
                }
                if let Some(m) = handle.get_webview_window("main") {
                    let _ = m.show();
                    let _ = m.set_focus();
                }
            });

            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
