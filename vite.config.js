import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";
import { nativephpMobile } from "./vendor/nativephp/mobile/resources/js/vite-plugin.js";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js", "resources/css/filament/admin/theme.css"],
            refresh: true,
            fonts: [
                bunny("Instrument Sans", {
                    weights: [400, 500, 600]
                })
            ]
        }),
        tailwindcss(),
        nativephpMobile()
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"]
        }
    }
});
