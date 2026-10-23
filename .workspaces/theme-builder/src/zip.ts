// Zip helpers shared by theme:pack and theme:package.
import { readFile, readdir, stat, writeFile } from "node:fs/promises";
import { dirname, join, relative } from "node:path";
import { spawnSync } from "node:child_process";

/** Cross-platform zip (PowerShell on Windows, zip CLI, or store-only fallback). */
export async function writeZipFromDir(
  sourceDir: string,
  zipPath: string,
  rootName: string,
  exclude: string[] = []
): Promise<void> {
  if (process.platform === "win32") {
    const ps = spawnSync(
      "powershell.exe",
      [
        "-NoProfile",
        "-Command",
        `Compress-Archive -Path '${sourceDir.replace(/'/g, "''")}' -DestinationPath '${zipPath.replace(/'/g, "''")}' -Force`,
      ],
      { stdio: "inherit" }
    );
    if (ps.status === 0) return;
  }

  const zip = spawnSync("zip", ["-qr", zipPath, rootName, "-x", "*/~cache/*", "*/node_modules/*", ...exclude], {
    cwd: dirname(sourceDir),
    stdio: "inherit",
  });
  if (zip.status === 0) return;

  // Same exclusions as `zip -x` (a `*` matches across `/`).
  const rx = exclude.map((g) => new RegExp("^" + g.replace(/[.+?^${}()|[\]\\]/g, "\\$&").replace(/\*/g, ".*") + "$"));
  const { entries } = await collectFiles(sourceDir);
  const kept = entries.filter((f) => {
    const rel = rootName + "/" + relative(sourceDir, f).replace(/\\/g, "/");
    return !rx.some((r) => r.test(rel));
  });
  await writeStoreZip(kept, sourceDir, zipPath, rootName);
}

async function collectFiles(
  dir: string
): Promise<{ entries: string[] }> {
  const entries: string[] = [];
  async function walk(current: string): Promise<void> {
    for (const name of await readdir(current)) {
      const full = join(current, name);
      const s = await stat(full);
      if (s.isDirectory()) {
        if (name === "~cache" || name === "node_modules") continue;
        await walk(full);
      } else {
        entries.push(full);
      }
    }
  }
  await walk(dir);
  return { entries };
}

/** Minimal ZIP (store, no compression) — portable fallback. */
async function writeStoreZip(
  files: string[],
  sourceDir: string,
  zipPath: string,
  rootName: string
): Promise<void> {
  const chunks: Buffer[] = [];
  const central: Buffer[] = [];
  let offset = 0;

  for (const file of files) {
    const rel = rootName + "/" + relative(sourceDir, file).replace(/\\/g, "/");
    const data = await readFile(file);
    const nameBuf = Buffer.from(rel, "utf8");
    const local = Buffer.alloc(30 + nameBuf.length);
    local.writeUInt32LE(0x04034b50, 0);
    local.writeUInt16LE(20, 4);
    local.writeUInt16LE(0, 6);
    local.writeUInt16LE(0, 8);
    local.writeUInt16LE(0, 10);
    local.writeUInt16LE(0, 12);
    local.writeUInt32LE(0, 14); // crc placeholder 0 for store simplicity
    local.writeUInt32LE(data.length, 18);
    local.writeUInt32LE(data.length, 22);
    local.writeUInt16LE(nameBuf.length, 26);
    local.writeUInt16LE(0, 28);
    nameBuf.copy(local, 30);

    const crc = crc32(data);
    local.writeUInt32LE(crc, 14);

    chunks.push(local, data);

    const cen = Buffer.alloc(46 + nameBuf.length);
    cen.writeUInt32LE(0x02014b50, 0);
    cen.writeUInt16LE(20, 4);
    cen.writeUInt16LE(20, 6);
    cen.writeUInt16LE(0, 8);
    cen.writeUInt16LE(0, 10);
    cen.writeUInt16LE(0, 12);
    cen.writeUInt16LE(0, 14);
    cen.writeUInt32LE(crc, 16);
    cen.writeUInt32LE(data.length, 20);
    cen.writeUInt32LE(data.length, 24);
    cen.writeUInt16LE(nameBuf.length, 28);
    cen.writeUInt16LE(0, 30);
    cen.writeUInt16LE(0, 32);
    cen.writeUInt16LE(0, 34);
    cen.writeUInt16LE(0, 36);
    cen.writeUInt32LE(0, 38);
    cen.writeUInt32LE(offset, 42);
    nameBuf.copy(cen, 46);
    central.push(cen);

    offset += local.length + data.length;
  }

  const centralStart = offset;
  const centralBuf = Buffer.concat(central);
  const end = Buffer.alloc(22);
  end.writeUInt32LE(0x06054b50, 0);
  end.writeUInt16LE(0, 4);
  end.writeUInt16LE(0, 6);
  end.writeUInt16LE(files.length, 8);
  end.writeUInt16LE(files.length, 10);
  end.writeUInt32LE(centralBuf.length, 12);
  end.writeUInt32LE(centralStart, 16);
  end.writeUInt16LE(0, 20);

  await writeFile(zipPath, Buffer.concat([...chunks, centralBuf, end]));
}

function crc32(buf: Buffer): number {
  let c = 0xffffffff;
  for (let i = 0; i < buf.length; i++) {
    c ^= buf[i];
    for (let k = 0; k < 8; k++) {
      c = c & 1 ? (c >>> 1) ^ 0xedb88320 : c >>> 1;
    }
  }
  return (c ^ 0xffffffff) >>> 0;
}

