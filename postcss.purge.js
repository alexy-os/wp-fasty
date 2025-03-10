const postcss = require('postcss');
const fs = require('fs');
const purgeUnused = require('./purge-unused');

// Read the original CSS
const css = fs.readFileSync('./wp-fasty-oop/theme.css', 'utf8');

// Run PostCSS with our plugin
postcss([purgeUnused()])
  .process(css, { from: './wp-fasty-oop/theme.css', to: './wp-fasty-oop/theme.min.css' })
  .then(result => {
    fs.writeFileSync('./wp-fasty-oop/theme.min.css', result.css);
    console.log('✅ CSS cleaned successfully!');
  })
  .catch(error => {
    console.error('❌ Error:', error);
    process.exit(1);
  }); 