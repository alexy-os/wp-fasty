import { getTheme } from '@/utils/theme';

export function SiteLogo() {
  const theme = getTheme();

  return (
    <div className="flex items-center gap-2">
      {theme === 'semantic' ? <span className="latty latty-box text-teal-500"></span> : <span className="latty latty-boxes text-blue-500"></span>}
      <span className="text-lg font-bold">{theme === 'semantic' ? 'Semantic' : 'UI8Kit'}</span>
    </div>
  );
}