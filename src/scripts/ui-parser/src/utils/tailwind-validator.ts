import postcss from 'postcss';
import tailwindcss from 'tailwindcss';

export class TailwindValidator {
  private validClassesCache = new Map<string, boolean>();

  /**
   * Validates an array of classes for Tailwind compatibility
   */
  async filterTailwindClasses(classes: string[]): Promise<string[]> {
        const uniqueClasses = [...new Set(classes.filter(Boolean))];
    
        const uncachedClasses = uniqueClasses.filter(
      cls => !this.validClassesCache.has(cls)
    );

    if (uncachedClasses.length === 0) {
      return uniqueClasses.filter(cls => this.validClassesCache.get(cls));
    }

        const css = uncachedClasses
      .map((cls, i) => `.test-${i} { @apply ${cls}; }`)
      .join('\n');

    try {
      await postcss([tailwindcss]).process(css);
            uncachedClasses.forEach(cls => this.validClassesCache.set(cls, true));
      return uniqueClasses;
    } catch (error) {
            const invalidClasses = new Set(
        String(error)
          .match(/`([^`]+)`/g)
          ?.map(m => m.replace(/`/g, '')) || []
      );

            uncachedClasses.forEach(cls => {
        this.validClassesCache.set(cls, !invalidClasses.has(cls));
      });

      return uniqueClasses.filter(cls => !invalidClasses.has(cls));
    }
  }

  /**
   * Filters class string
   */
  async filterClassString(classString: string): Promise<string> {
    const classes = classString.split(' ').filter(Boolean);
    const validClasses = await this.filterTailwindClasses(classes);
    return validClasses.join(' ');
  }
}

export const tailwindValidator = new TailwindValidator(); 