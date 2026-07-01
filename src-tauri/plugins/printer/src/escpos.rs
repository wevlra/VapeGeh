use serde::Deserialize;

#[derive(Debug, Deserialize)]
pub struct ReceiptItem {
    pub product: String,
    pub qty: i64,
    pub price: Option<f64>,
    pub subtotal: Option<f64>,
}

#[derive(Debug, Deserialize)]
pub struct ReceiptPayload {
    pub ref_number: String,
    pub type_label: String,
    pub date: String,
    pub staff: String,
    pub location: String,
    pub buyer: Option<String>,
    pub items: Vec<ReceiptItem>,
    pub total: Option<f64>,
    pub paid_amount: Option<f64>,
    pub change: Option<f64>,
    pub payment_method: Option<String>,
    pub additional_costs: Option<Vec<serde_json::Value>>,
    pub notes: Option<String>,
}

pub fn render_escpos(data: &ReceiptPayload) -> Vec<u8> {
    // ESC/POS constants
    const ESC: u8 = 0x1B;
    const GS: u8 = 0x1D;

    let mut buf = Vec::new();

    // Initialize printer
    buf.extend_from_slice(&[ESC, b'@']);

    // Center-align, double-height for header
    buf.extend_from_slice(&[GS, b'!', 0x11]);  // double-height, double-width
    buf.extend_from_slice(&[ESC, b'a', 0x01]);  // center
    writeln_buf(&mut buf, &data.location);

    // Reset print mode
    buf.extend_from_slice(&[GS, b'!', 0x00]);
    writeln_buf(&mut buf, "");

    // Separator
    buf.extend_from_slice(&[ESC, b'a', 0x00]);  // left
    writeln_buf(&mut buf, &"-".repeat(32));

    // Reference
    buf.extend_from_slice(&[ESC, b'E', 0x01]);  // bold on
    writeln_buf(&mut buf, &format!("#{}", data.ref_number));
    buf.extend_from_slice(&[ESC, b'E', 0x00]);  // bold off

    writeln_buf(&mut buf, &format!("{} | {}", data.type_label, data.date));
    writeln_buf(&mut buf, &format!("Staf: {}", data.staff));
    if let Some(ref buyer) = data.buyer {
        writeln_buf(&mut buf, &format!("Pembeli: {}", buyer));
    }

    // Separator
    writeln_buf(&mut buf, &"-".repeat(32));

    // Items header
    if !data.items.is_empty() && data.items[0].price.is_some() {
        writeln_buf(&mut buf, &format!("{:<20} {:>4} {:>8}", "Item", "Jml", "Harga"));
        writeln_buf(&mut buf, &"-".repeat(32));

        for item in &data.items {
            let price_str = item.price.map(|p| format!("{:>8}", format_amount(p))).unwrap_or_default();
            writeln_buf(
                &mut buf,
                &format!("{:<20} {:>4} {:>8}", &truncate(&item.product, 20), item.qty, price_str.trim()),
            );
        }
    } else {
        writeln_buf(&mut buf, &format!("{:<20} {:>4}", "Item", "Jml"));
        writeln_buf(&mut buf, &"-".repeat(32));
        for item in &data.items {
            writeln_buf(
                &mut buf,
                &format!("{:<20} {:>4}", &truncate(&item.product, 20), item.qty),
            );
        }
    }

    // Separator
    writeln_buf(&mut buf, &"-".repeat(32));

    // Totals
    if let Some(total) = data.total {
        writeln_buf(&mut buf, &format!("{:>32}", format!("Total: Rp {}", format_amount(total))));
    }
    if let Some(paid) = data.paid_amount {
        writeln_buf(&mut buf, &format!("{:>32}", format!("Bayar: Rp {}", format_amount(paid))));
    }
    if let Some(change) = data.change {
        writeln_buf(&mut buf, &format!("{:>32}", format!("Kembali: Rp {}", format_amount(change))));
    }
    if let Some(ref pm) = data.payment_method {
        writeln_buf(&mut buf, &format!("Metode: {}", pm));
    }

    // Additional costs
    if let Some(ref costs) = data.additional_costs {
        if !costs.is_empty() {
            writeln_buf(&mut buf, "");
            writeln_buf(&mut buf, "Biaya Tambahan:");
            for cost in costs {
                let desc = cost.get("description").and_then(|v| v.as_str()).unwrap_or("");
                let amount = cost.get("amount").and_then(|v| v.as_f64()).unwrap_or(0.0);
                writeln_buf(&mut buf, &format!("  {}: Rp {}", desc, format_amount(amount)));
            }
        }
    }

    // Notes
    if let Some(ref notes) = data.notes {
        writeln_buf(&mut buf, "");
        writeln_buf(&mut buf, &format!("Catatan: {}", notes));
    }

    // Footer
    writeln_buf(&mut buf, "");
    buf.extend_from_slice(&[ESC, b'a', 0x01]);  // center
    writeln_buf(&mut buf, "Terima kasih!");
    writeln_buf(&mut buf, "Barang yang sudah dibeli tidak dapat dikembalikan.");

    // Cut paper
    buf.extend_from_slice(&[GS, b'V', 0x00]);

    buf
}

fn writeln_buf(buf: &mut Vec<u8>, s: &str) {
    buf.extend_from_slice(s.as_bytes());
    buf.push(0x0A); // LF
}

fn truncate(s: &str, max: usize) -> String {
    if s.len() > max {
        format!("{}..", &s[..max.saturating_sub(2)])
    } else {
        s.to_string()
    }
}

fn format_amount(amount: f64) -> String {
    let rounded = amount.round() as i64;
    let s = rounded.to_string();
    let mut result = String::new();
    for (i, c) in s.chars().rev().enumerate() {
        if i > 0 && i % 3 == 0 {
            result.insert(0, '.');
        }
        result.insert(0, c);
    }
    result
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_render_escpos_produces_bytes() {
        let payload = ReceiptPayload {
            ref_number: "INV-001".into(),
            type_label: "STOK KELUAR".into(),
            date: "30 Jun 2026 14:00".into(),
            staff: "Admin".into(),
            location: "Toko Utama".into(),
            buyer: Some("Budi".into()),
            items: vec![
                ReceiptItem {
                    product: "Vape Pod".into(),
                    qty: 2,
                    price: Some(50000.0),
                    subtotal: Some(100000.0),
                },
            ],
            total: Some(100000.0),
            paid_amount: Some(100000.0),
            change: Some(0.0),
            payment_method: Some("Tunai".into()),
            additional_costs: None,
            notes: None,
        };

        let bytes = render_escpos(&payload);
        assert!(!bytes.is_empty());
        // Starts with ESC @ (init)
        assert_eq!(bytes[0], 0x1B);
        assert_eq!(bytes[1], b'@');
        // Ends with GS V NUL (cut) - 3 bytes
        assert_eq!(bytes[bytes.len() - 3], 0x1D);
        assert_eq!(bytes[bytes.len() - 2], b'V');
        assert_eq!(bytes[bytes.len() - 1], 0x00);
    }

    #[test]
    fn test_format_amount_indonesian() {
        assert_eq!(format_amount(100000.0), "100.000");
        assert_eq!(format_amount(5000.0), "5.000");
        assert_eq!(format_amount(100.0), "100");
    }
}
