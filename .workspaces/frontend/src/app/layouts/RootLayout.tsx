import { RootLayoutSemantic } from './RootLayoutSemantic'
import { RootLayoutUI8Kit } from './RootLayoutUI8Kit'

export function RootLayout(props: any) {
  return process.env.DEFAULT_LAYOUT === 'semantic'
    ? <RootLayoutSemantic {...props} />
    : <RootLayoutUI8Kit {...props} />
}