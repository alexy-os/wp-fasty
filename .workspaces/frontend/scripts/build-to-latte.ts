import { convertReactToLatte } from './react-to-latte';
import path from 'path';

async function main() {
  //const frontPagePath = path.resolve(__dirname, '../src/uikits/latte/components/NavbarTemplate.tsx');
  //const outputPath = path.resolve(__dirname, '../src/templates/latte/navbar.latte');
  const frontPagePath = path.resolve(__dirname, '../src/uikits/latte/components/FrontPageTemplate.tsx');
  const outputPath = path.resolve(__dirname, '../src/templates/latte/front-page.latte');

  console.log('Starting conversion...');

  try {
    await convertReactToLatte(frontPagePath, outputPath);
    console.log('Conversion completed successfully!');
  } catch (error) {
    console.error('Conversion failed:', error);
    process.exit(1);
  }
}

main();
