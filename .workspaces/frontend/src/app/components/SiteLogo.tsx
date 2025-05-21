import { getTheme } from '@/utils/theme';
import { Box, Boxes } from 'lucide-react';

export function SiteLogo() {
  const theme = getTheme();

  return (
    <div className="flex items-center gap-2">
      {theme === 'semantic' ? <Box color="#14b8a6" className="w-5 h-5" /> : <Boxes color="#0ea5e9" className="w-5 h-5" />}
      <span className="text-lg font-bold">{theme === 'semantic' ? 'Semantic' : 'UI8Kit'}</span>
    </div>
  );
}