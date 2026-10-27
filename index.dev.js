try {
  await import("http://localhost:5177/src/index.js");
} catch (error) {
  console.error(
    "[kirbyup] Couldn't load the plugin from the development server at http://localhost:5177/src/index.js. If it isn't running, run `npm run dev` to start Vite, or build the plugin with `npm run build` so Kirby uses the production version."
  );
  throw error;
}
