import { Server } from "node:net";
import { runCLI } from "@wp-playground/cli";
import path from "node:path";
export async function start(port = 8881, extraSteps = []) {
  const originalListen = Server.prototype.listen;
  // Playground 3.1 has no host option. Constrain its listener before it opens.
  Server.prototype.listen = function (...args) {
    if (args[0] === port && typeof args[1] === "function")
      return originalListen.call(this, port, "127.0.0.1", args[1]);
    return originalListen.apply(this, args);
  };
  try {
    return await runCLI({
      command: "server",
      php: "8.3",
      wp: process.env.SJ_WP_VERSION || "7.1.2",
      port,
      login: false,
      quiet: true,
      internalCookieStore: false,
      "site-url": `http://127.0.0.1:${port}`,
      mount: [
        {
          hostPath: path.resolve(
            process.env.SJ_THEME_PATH || "wp-content/themes/swatijha-theme",
          ),
          vfsPath: "/wordpress/wp-content/themes/swatijha-theme",
        },
        {
          hostPath: path.resolve(
            process.env.SJ_PLUGIN_PATH || "wp-content/plugins/swatijha-core",
          ),
          vfsPath: "/wordpress/wp-content/plugins/swatijha-core",
        },
      ],
      blueprint: {
        constants: {
          WP_ENVIRONMENT_TYPE: "local",
          WP_DEBUG: true,
          WP_DEBUG_DISPLAY: false,
          DISALLOW_FILE_EDIT: true,
        },
        steps: [
          {
            step: "activatePlugin",
            pluginPath: "swatijha-core/swatijha-core.php",
          },
          { step: "activateTheme", themeFolderName: "swatijha-theme" },
          {
            step: "runPHP",
            code: `<?php require '/wordpress/wp-load.php'; update_option('blogname','Professor Swati Jha'); update_option('blog_public',0); update_option('permalink_structure','/%postname%/'); update_option('timezone_string','Europe/London');`,
          },
          ...extraSteps,
        ],
      },
    });
  } finally {
    Server.prototype.listen = originalListen;
  }
}
