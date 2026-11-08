import type {TemplatePage} from './types';
export type PageShell = 'standard' | 'blank' | 'sidebar';
export const onboardingCopy = {label:'Choose your page layout',standard:'Standard',blank:'Blank',sidebar:'With sidebar',screens:'Start from a screen',manual:'Open manual Builder',pending:'Agent is working. Your published page is unchanged.',preview:'Screen preview'};
export const shellChoices: {id:PageShell;label:string}[] = [{id:'standard',label:onboardingCopy.standard},{id:'blank',label:onboardingCopy.blank},{id:'sidebar',label:onboardingCopy.sidebar}];
export function canOnboard(marked:boolean,published:boolean,count:number){return marked&&!published&&count===0;}
export function screenCards(pages:TemplatePage[]){return pages.filter(p=>p.preview&&p.blocks.length>0).slice(0,12);}
