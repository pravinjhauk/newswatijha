import sharp from "sharp";
const path = "wp-content/themes/swatijha-theme/assets/images/";
for (const width of [320, 453])
  await sharp(`${path}professor-swati-jha.jpg`)
    .resize({ width, withoutEnlargement: true })
    .webp({ quality: 84 })
    .toFile(`${path}professor-swati-jha-${width}.webp`);
console.log("Created bounded portrait derivatives; original retained.");
