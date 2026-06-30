package com.wevlra.vapegeh;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

/**
 * Helper to build ESC/POS byte arrays for thermal receipt printers.
 */
public class ESCPrinter {

    // ESC/POS commands
    private static final byte ESC = 0x1B;
    private static final byte GS = 0x1D;
    private static final byte LF = 0x0A;
    private static final byte FF = 0x0C;

    private final ByteArrayOutputStream buf = new ByteArrayOutputStream();

    public ESCPrinter init() {
        buf.write(ESC);
        buf.write('@');
        return this;
    }

    public ESCPrinter feed(int lines) {
        for (int i = 0; i < lines; i++) {
            buf.write(LF);
        }
        return this;
    }

    public ESCPrinter cut() {
        buf.write(GS);
        buf.write('V');
        buf.write(0x42); // full cut with feed
        buf.write(0x00);
        return this;
    }

    public ESCPrinter center() {
        buf.write(ESC);
        buf.write('a');
        buf.write(0x01);
        return this;
    }

    public ESCPrinter left() {
        buf.write(ESC);
        buf.write('a');
        buf.write(0x00);
        return this;
    }

    public ESCPrinter bold(boolean on) {
        buf.write(ESC);
        buf.write('E');
        buf.write(on ? (byte) 0x01 : (byte) 0x00);
        return this;
    }

    public ESCPrinter textSize(int multiplierW, int multiplierH) {
        // 0x00 = normal, 0x10 = 2x width, 0x01 = 2x height, 0x11 = 2x both
        int val = 0;
        if (multiplierW > 1) val |= 0x10;
        if (multiplierH > 1) val |= 0x01;
        buf.write(GS);
        buf.write('!');
        buf.write(val);
        return this;
    }

    public ESCPrinter text(String text) {
        if (text == null) return this;
        byte[] bytes = text.getBytes(StandardCharsets.UTF_8);
        buf.write(bytes, 0, bytes.length);
        return this;
    }

    public ESCPrinter line(String line) {
        text(line);
        buf.write(LF);
        return this;
    }

    public ESCPrinter divider() {
        line("--------------------------------");
        return this;
    }

    public ESCPrinter printBitmap(Bitmap bitmap) {
        if (bitmap == null) return this;

        int w = bitmap.getWidth();
        int h = bitmap.getHeight();

        // Convert to 1-bit monochrome
        int[] pixels = new int[w * h];
        bitmap.getPixels(pixels, 0, w, 0, 0, w, h);

        // ESC/POS raster format (GS v 0)
        int xBytes = (w + 7) / 8;

        buf.write(GS);
        buf.write('v');
        buf.write(0x30); // raster bit image mode 0
        buf.write((byte) (xBytes & 0xFF));
        buf.write((byte) ((xBytes >> 8) & 0xFF));
        buf.write((byte) (h & 0xFF));
        buf.write((byte) ((h >> 8) & 0xFF));

        for (int y = 0; y < h; y++) {
            for (int xByte = 0; xByte < xBytes; xByte++) {
                byte b = 0;
                for (int bit = 0; bit < 8; bit++) {
                    int px = xByte * 8 + bit;
                    if (px < w) {
                        int pixel = pixels[y * w + px];
                        int r = (pixel >> 16) & 0xFF;
                        int g = (pixel >> 8) & 0xFF;
                        int b2 = pixel & 0xFF;
                        int gray = (r * 299 + g * 587 + b2 * 114) / 1000;
                        if (gray < 128) {
                            b |= (byte) (1 << (7 - bit));
                        }
                    }
                }
                buf.write(b);
            }
        }

        return this;
    }

    public ESCPrinter newLine() {
        buf.write(LF);
        return this;
    }

    public byte[] toByteArray() {
        return buf.toByteArray();
    }

    /**
     * Download an image from a URL and decode it to a Bitmap.
     */
    public static Bitmap downloadBitmap(String urlString) {
        try {
            URL url = new URL(urlString);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setConnectTimeout(5000);
            conn.setReadTimeout(5000);
            conn.setInstanceFollowRedirects(true);
            InputStream is = conn.getInputStream();
            Bitmap bitmap = BitmapFactory.decodeStream(is);
            is.close();
            return bitmap;
        } catch (Exception e) {
            return null;
        }
    }
}
