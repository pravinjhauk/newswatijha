for (let attempt = 0; attempt < 60; attempt++) {
  try {
    const response = await fetch("http://127.0.0.1:8881");
    if (response.ok) process.exit(0);
  } catch {
    /* The preview is still starting. */
  }
  await new Promise((resolve) => setTimeout(resolve, 1000));
}
throw new Error("Local WordPress did not become ready.");
