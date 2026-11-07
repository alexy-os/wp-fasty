/** Offline build-only compatibility test. Never authenticates or runs an agent.
 * node tools/sdk/browser-bundle-probe.mjs /path/to/existing/agent-project /tmp/evidence
 */
import { createRequire } from 'node:module';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
const project = resolve(process.argv[2] ?? '.');
const out = resolve(process.argv[3] ?? '/tmp/wpfasty-sdk-probe');
const require = createRequire(project + '/package.json');
const esbuild = require('esbuild');
mkdirSync(out, { recursive: true });
for (const pkg of ['@grok/agent-sdk', '@cursor/sdk', '@cursor/sdk/bundled']) {
  const packageName = pkg.startsWith('@cursor/') ? '@cursor/sdk' : '@grok/agent-sdk';
  const version = JSON.parse(readFileSync(resolve(project, 'node_modules', packageName, 'package.json'), 'utf8')).version;
  for (const platform of ['browser', 'node']) {
    const options = { stdin: { contents: `import * as sdk from '${pkg}'; globalThis.sdkProbe = sdk;`, resolveDir: project }, bundle: true, platform, format: 'esm', write: false, logLevel: 'silent' };
    // bun:sqlite is intentionally external only in the Node comparison. This is
    // not proof of runtime usability; the documented JSONL store is still needed.
    if (platform === 'node') options.external = ['bun:sqlite'];
    let result;
    try {
      const built = await esbuild.build(options);
      result = { pkg, version, platform, success: true, bytes: built.outputFiles[0].contents.length, executed: false, external: options.external ?? [] };
    } catch (e) {
      result = { pkg, version, platform, success: false, executed: false, errors: e.errors.map(({ text, location }) => ({ text, file: location?.file })) };
    }
    writeFileSync(resolve(out, `${pkg.replaceAll(/[\/@]/g, '_')}-${platform}.json`), JSON.stringify(result, null, 2));
    console.log(pkg, version, platform, result.success ? 'BUILD_OK_NOT_EXECUTED' : `BUILD_FAILED ${result.errors.length}`);
  }
}
