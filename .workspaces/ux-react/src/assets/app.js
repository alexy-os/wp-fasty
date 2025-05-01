import { registerReactControllerComponents } from '@symfony/ux-react';

// Регистрируем все React компоненты из директории controllers
registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
