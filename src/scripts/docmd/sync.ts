import { DocGenerator } from './generator';
import fs from 'fs/promises';
import path from 'path';

async function findMarkdownFiles(dir: string): Promise<string[]> {
    const files: string[] = [];
    const entries = await fs.readdir(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            files.push(...await findMarkdownFiles(fullPath));
        } else if (entry.name.endsWith('.md')) {
            files.push(fullPath);
        }
    }

    return files;
}

async function syncExtensions() {
    const generator = new DocGenerator();
    await generator.loadExtensions();

    console.log('Scanning markdown files...');
    const mdFiles = await findMarkdownFiles('./docs');
    
    console.log(`Found ${mdFiles.length} markdown files`);
    
    for (const mdFile of mdFiles) {
        console.log(`Processing ${mdFile}...`);
        await generator.updateExtensionsFromMd(mdFile);
    }

    console.log('Extensions synchronized successfully!');
}

// Запускаем синхронизацию
syncExtensions().catch(error => {
    console.error('Error synchronizing extensions:', error);
    process.exit(1);
}); 