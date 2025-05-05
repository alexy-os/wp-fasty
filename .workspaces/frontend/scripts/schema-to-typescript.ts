// cd .workspaces/frontend && bun run scripts/schema-to-typescript.ts
import { compile } from 'json-schema-to-typescript';
import fs from 'node:fs';

// Load schema
const schema = JSON.parse(fs.readFileSync('./source/context.schema.json', 'utf8'));

compile(schema, 'Context').then(ts => {
  fs.writeFileSync('./types/WPFastY-YM-ContextTypes.ts', ts);
  console.log('TypeScript types successfully generated');
});