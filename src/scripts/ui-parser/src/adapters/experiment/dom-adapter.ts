import fs from 'fs';
import path from 'path';
import { JSDOM } from 'jsdom';
import { exec } from 'child_process';
import { promisify } from 'util';
import { ClassExtractorAdapter } from './base-adapter';
import { EnhancedClassEntry } from '../../core/types';
import { CONFIG } from '../../config';
import { deduplicateEntries } from '../../utils/deduplication';

const execAsync = promisify(exec);

/**
 * ❌ WARNING: This is an experimental version of the adapter and does not work as expected. There are problems with DOM parsing.
 */

/**
 * DOM adapter for extracting classes
 */
export class DOMExtractorAdapter implements ClassExtractorAdapter {
  readonly name = 'DOM Extractor';
  
  /**
   * Checks if the adapter supports the given component
   */
  supportsComponent(componentPath: string): boolean {
    const ext = path.extname(componentPath).toLowerCase();
    return ['.tsx', '.jsx'].includes(ext);
  }
  
  /**
   * Analyzes the component and extracts classes through DOM
   */
  async extractClasses(componentPath: string): Promise<EnhancedClassEntry[]> {
    
    
    try {
      const document = await this.renderComponentToDOM(componentPath);
      
      if (document) {
        const entries = this.extractClassesFromDOM(document, componentPath);
        console.log(`Found ${entries.length} raw class entries in rendered DOM`);
        
        
        const uniqueEntries = deduplicateEntries(entries);
        
        console.log(`After deduplication: ${uniqueEntries.length} unique class entries`);
        return uniqueEntries;
      }
      
      console.log('Failed to render component');
      return [];
    } catch (error) {
      console.error(`Error analyzing component:`, error);
      return [];
    }
  }
  
  /**
   * Renders the component to DOM
   */
  private async renderComponentToDOM(componentPath: string): Promise<Document | null> {
    try {
      const componentName = path.basename(componentPath, path.extname(componentPath));
      const tempDir = path.resolve('./temp');
      
      if (!fs.existsSync(tempDir)) {
        fs.mkdirSync(tempDir, { recursive: true });
      }
      
      
      const packageJsonPath = path.join(tempDir, 'package.json');
      fs.writeFileSync(packageJsonPath, JSON.stringify({ 
        "type": "module",
        "private": true
      }));
      
      
      const tempFile = path.join(tempDir, `render-${componentName}.js`);
      
      
      const renderCode = `
        import React from 'react';
        import { renderToString } from 'react-dom/server';
        import { ${componentName} } from '${componentPath.replace(/\\/g, '/')}';
        
        
        try {
          const html = renderToString(React.createElement(${componentName}));
          console.log(html);
        } catch (error) {
          console.error('Error rendering component:', error);
          process.exit(1);
        }
      `;
      
      fs.writeFileSync(tempFile, renderCode);
      
      
      const packageManager = this.detectPackageManager();
      let command: string;
      
      switch (packageManager) {
        case 'pnpm':
          command = `pnpm tsx "${tempFile}"`;
          break;
        case 'yarn':
          command = `yarn tsx "${tempFile}"`;
          break;
        case 'bun':
          command = `bun run "${tempFile}"`;
          break;
        default:
          command = `npx tsx "${tempFile}"`;
      }
      
      try {
        const { stdout, stderr } = await execAsync(command, { 
          encoding: 'utf-8',
          cwd: tempDir 
        });
        
        
        fs.unlinkSync(tempFile);
        fs.unlinkSync(packageJsonPath);
        
        if (stderr && !stdout) {
          console.error(`Error rendering ${componentName}:`, stderr);
          return null;
        }
        
        
        const dom = new JSDOM(`<!DOCTYPE html><html><body>${stdout}</body></html>`);
        return dom.window.document;
      } catch (error) {
        console.error(`Error executing render script for ${componentName}:`, error);
        
        
        if (fs.existsSync(tempFile)) {
          fs.unlinkSync(tempFile);
        }
        if (fs.existsSync(packageJsonPath)) {
          fs.unlinkSync(packageJsonPath);
        }
        
        return null;
      }
    } catch (error) {
      console.error(`Failed to render component:`, error);
      return null;
    }
  }
  
  /**
   * Extracts classes from DOM
   */
  private extractClassesFromDOM(document: Document, componentPath: string): EnhancedClassEntry[] {
    const componentName = path.basename(componentPath, path.extname(componentPath));
    const componentDir = path.dirname(componentPath);
    const classEntries: EnhancedClassEntry[] = [];
    
    
    document.querySelectorAll('*[class]').forEach(element => {
      const classes = element.getAttribute('class') || '';
      const elementType = element.tagName.toLowerCase();
      
      
      if (!classes.trim()) return;
      
      console.log(`Found element: ${elementType} with classes: ${classes}`);
      
      
      const classEntry: EnhancedClassEntry = {
        quark: this.generateQuarkName(classes),
        semantic: this.generateSemanticName(componentName, elementType, classes), 
        classes,
        componentName,
        elementType,
        variants: {}, 
        isPublic: true,
        components: {
          [componentName]: {
            path: componentDir,
            name: componentName
          }
        }
      };
      
      classEntries.push(classEntry);
    });
    
    return classEntries;
  }
  
  /**
   * Generates quark name
   */
  private generateQuarkName(classes: string): string {
    const normalizedClasses = this.normalizeClassString(classes);
    
    const quarkId = normalizedClasses
      .split(' ')
      .map(cls => {
        
        const parts = cls.split(':');
        const baseCls = parts[parts.length - 1];
        
        
        const cleanCls = baseCls
          .replace(/[\[\]\/\(\)]/g, '') 
          .replace(/[&>~=]/g, '')       
          .replace(/[^a-zA-Z0-9-_]/g, ''); 
        
        if (cleanCls.match(/^\d/)) {
          
          return cleanCls.replace(/[^\d]/g, '');
        }
        
        
        return cleanCls
          .split('-')
          .map(word => word[0] || '')
          .join('')
          .toLowerCase();
      })
      .join('');

    return `${CONFIG.classNames.quarkPrefix}${quarkId}`;
  }
  
  /**
   * Generates semantic name
   */
  private generateSemanticName(componentName: string, elementType: string, classes: string): string {
    const normalizedClasses = this.normalizeClassString(classes);
    const classIdentifier = normalizedClasses
      .split(' ')
      .map(cls => {
        
        const baseCls = cls.split(':').pop() || '';
        
        
        return baseCls
          .replace(/[\[\]\/\(\)]/g, '-') 
          .replace(/[&>~=]/g, '')        
          .replace(/[^a-zA-Z0-9-_]/g, '') 
          .replace(/-+/g, '-')           
          .replace(/^-|-$/g, '');        
      })
      .filter(Boolean) 
      .join('-');

    return `${CONFIG.classNames.semanticPrefix}${componentName.toLowerCase()}-${elementType}${classIdentifier ? `-${classIdentifier}` : ''}`;
  }
  
  /**
   * Normalizes class string
   */
  private normalizeClassString(classString: string): string {
    return classString.split(' ').sort().join(' ');
  }
  
  /**
   * Detects the current package manager
   */
  private detectPackageManager(): string {
    if (fs.existsSync('pnpm-lock.yaml')) return 'pnpm';
    if (fs.existsSync('yarn.lock')) return 'yarn';
    if (fs.existsSync('bun.lockb')) return 'bun';
    return 'npm'; 
  }
} 