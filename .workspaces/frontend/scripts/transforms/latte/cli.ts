// cd .workspaces/frontend && bun run scripts/transforms/latte/cli.ts
// bun run scripts/transforms/latte/cli.ts <input-file.tsx> <output-file.latte>
import { TemplatesBuildConfig } from '@scripts/config/TemplatesBuildConfig';
import { convertReactToLatte } from './template-converter';
import path from 'node:path';

const { uikitDir, templatesDir, engine } = TemplatesBuildConfig;

// CLI interface
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.length < 2) {
    console.log('Usage: bun run scripts/transforms/latte/cli.ts <input-file.tsx> <output-file.latte>');
    process.exit(1);
  }

  const inputFile = path.resolve(uikitDir, engine, 'components', args[0]);
  const outputFile = path.resolve(templatesDir, engine, args[1]);

  convertReactToLatte(inputFile, outputFile)
    .then(success => process.exit(success ? 0 : 1));
}

export { convertReactToLatte };
