package com.wevlra.vapegeh;

import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothSocket;
import android.content.Context;
import android.graphics.Bitmap;
import android.util.Log;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.IOException;
import java.io.OutputStream;
import java.util.Set;
import java.util.UUID;

@CapacitorPlugin(
    name = "Printer",
    permissions = {
        @Permission(
            alias = "bluetooth",
            strings = {
                android.Manifest.permission.BLUETOOTH,
                android.Manifest.permission.BLUETOOTH_ADMIN,
                android.Manifest.permission.BLUETOOTH_CONNECT,
            }
        )
    }
)
public class PrinterPlugin extends Plugin {

    private static final String TAG = "PrinterPlugin";
    private static final UUID SPP_UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB");

    private String selectedAddress = null;

    @PluginMethod
    public void getPairedDevices(PluginCall call) {
        try {
            BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
            if (adapter == null) {
                call.reject("Bluetooth not supported on this device");
                return;
            }
            if (!adapter.isEnabled()) {
                call.reject("Bluetooth is disabled");
                return;
            }

            Set<BluetoothDevice> pairedDevices = adapter.getBondedDevices();
            JSArray result = new JSArray();

            if (pairedDevices != null) {
                for (BluetoothDevice device : pairedDevices) {
                    JSObject obj = new JSObject();
                    obj.put("name", device.getName() != null ? device.getName() : "Unknown");
                    obj.put("address", device.getAddress());
                    result.put(obj);
                }
            }

            JSObject ret = new JSObject();
            ret.put("devices", result);
            call.resolve(ret);
        } catch (SecurityException e) {
            call.reject("Bluetooth permission not granted: " + e.getMessage());
        }
    }

    @PluginMethod
    public void selectPrinter(PluginCall call) {
        String address = call.getString("address");
        if (address == null || address.isEmpty()) {
            call.reject("Printer address is required");
            return;
        }
        selectedAddress = address;

        // Persist to shared preferences
        Context context = getContext();
        if (context != null) {
            context.getSharedPreferences("vapegeh_printer", Context.MODE_PRIVATE)
                .edit()
                .putString("selected_address", address)
                .apply();
        }

        JSObject ret = new JSObject();
        ret.put("selected", true);
        call.resolve(ret);
    }

    @PluginMethod
    public void getSelectedPrinter(PluginCall call) {
        if (selectedAddress == null) {
            Context context = getContext();
            if (context != null) {
                selectedAddress = context.getSharedPreferences("vapegeh_printer", Context.MODE_PRIVATE)
                    .getString("selected_address", null);
            }
        }

        JSObject ret = new JSObject();
        if (selectedAddress != null) {
            ret.put("address", selectedAddress);
        }
        call.resolve(ret);
    }

    @PluginMethod
    public void printReceipt(PluginCall call) {
        JSObject payload = call.getObject("payload");
        if (payload == null) {
            call.reject("Payload is required");
            return;
        }

        // Resolve printer address
        String address = selectedAddress;
        if (address == null) {
            Context context = getContext();
            if (context != null) {
                address = context.getSharedPreferences("vapegeh_printer", Context.MODE_PRIVATE)
                    .getString("selected_address", null);
            }
        }
        if (address == null) {
            call.reject("NO_PRINTER_SELECTED");
            return;
        }

        // Print in background thread
        new Thread(() -> {
            try {
                printViaBluetooth(address, payload);
                call.resolve();
            } catch (Exception e) {
                Log.e(TAG, "Print failed", e);
                call.reject("Print failed: " + e.getMessage());
            }
        }).start();
    }

    private void printViaBluetooth(String address, JSObject payload) throws IOException {
        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
        if (adapter == null || !adapter.isEnabled()) {
            throw new IOException("Bluetooth not available");
        }

        BluetoothDevice device = adapter.getRemoteDevice(address);
        BluetoothSocket socket = device.createRfcommSocketToServiceRecord(SPP_UUID);

        try {
            socket.connect();
            OutputStream os = socket.getOutputStream();

            ESCPrinter printer = new ESCPrinter();
            printer.init();

            // --- Logo ---
            String logoUrl = payload.optString("logo_url", null);
            if (logoUrl != null && !logoUrl.isEmpty()) {
                Bitmap logo = ESCPrinter.downloadBitmap(logoUrl);
                if (logo != null) {
                    printer.center();
                    printer.printBitmap(logo);
                    printer.feed(1);
                }
            }

            // --- Store info ---
            JSONObject store = payload.optJSONObject("store");
            if (store != null) {
                printer.center();
                printer.textSize(2, 1);
                printer.line(store.optString("name", ""));
                printer.textSize(1, 1);
                printer.line(store.optString("address", ""));
                printer.line(store.optString("phone", ""));
            }

            printer.feed(1);
            printer.divider();

            // --- Reference & meta ---
            printer.left();
            printer.line("No: " + payload.optString("reference", ""));
            printer.line("Tipe: " + payload.optString("type", ""));
            printer.line("Tanggal: " + payload.optString("date", ""));
            printer.line("Kasir: " + payload.optString("cashier", ""));
            printer.line("Lokasi: " + payload.optString("location", ""));
            if (payload.has("buyer") && !payload.isNull("buyer")) {
                printer.line("Pembeli: " + payload.optString("buyer", ""));
            }

            printer.divider();

            // --- Items ---
            printer.bold(true);
            printer.line(String.format("%-18s %4s %8s", "Item", "Qty", "Harga"));
            printer.bold(false);

            JSONArray items = payload.optJSONArray("items");
            if (items != null) {
                for (int i = 0; i < items.length(); i++) {
                    JSONObject item = items.optJSONObject(i);
                    if (item == null) continue;
                    String name = item.optString("name", "");
                    int qty = item.optInt("qty", 0);
                    String price = formatRupiah(item.optDouble("subtotal", 0));

                    // Truncate name to ~20 chars for thermal column
                    if (name.length() > 18) name = name.substring(0, 16) + "..";
                    printer.line(String.format("%-18s %4d %8s", name, qty, price));
                }
            }

            printer.divider();

            // --- Total ---
            double total = payload.optDouble("total", 0);
            printer.bold(true);
            printer.line("Total: " + formatRupiah(total));
            printer.bold(false);

            String paymentMethod = payload.optString("payment_method", null);
            if (paymentMethod != null && !paymentMethod.isEmpty()) {
                printer.line("Pembayaran: " + paymentMethod);
            }

            // --- Notes ---
            if (payload.has("notes") && !payload.isNull("notes")) {
                printer.feed(1);
                printer.line("Catatan: " + payload.optString("notes", ""));
            }

            // --- Footer ---
            printer.feed(1);
            printer.center();
            printer.line("Terima kasih!");
            printer.line("Barang yang sudah dibeli");
            printer.line("tidak dapat dikembalikan.");

            printer.feed(4);
            printer.cut();

            os.write(printer.toByteArray());
            os.flush();
            socket.close();
        } catch (IOException e) {
            try { socket.close(); } catch (IOException ignored) {}
            throw e;
        }
    }

    private String formatRupiah(double amount) {
        java.text.DecimalFormat df = new java.text.DecimalFormat("#,###");
        return "Rp" + df.format((long) Math.round(amount));
    }
}
