// cd .workspaces/frontend && bun run scripts/schema-to-typescript.ts
import { compile } from 'json-schema-to-typescript';
import * as fs from 'node:fs';

// Load schema
const schema = JSON.parse(fs.readFileSync('../src/context/context.schema.json', 'utf8'));

compile(schema, 'Context').then(ts => {
  fs.writeFileSync('./src/context/types/wpfasty.ts', ts);
  console.log('TypeScript types successfully generated');
});