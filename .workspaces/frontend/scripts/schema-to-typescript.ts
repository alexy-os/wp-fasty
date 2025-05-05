// cd .workspaces/frontend/scripts && bun run schema-to-typescript.ts
import { compile } from 'json-schema-to-typescript';
import fs from 'node:fs';

// Load schema
const schema = JSON.parse(fs.readFileSync('./context.schema.json', 'utf8'));

compile(schema, 'Context').then(ts => {
  fs.writeFileSync('./ContextTypes.ts', ts);
  console.log('TypeScript types successfully generated');
});