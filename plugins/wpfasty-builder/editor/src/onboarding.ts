import type {Starter} from './types';
export type PageShell = 'standard' | 'blank' | 'sidebar';
export const onboardingCopy = {label:'Choose your page layout',standard:'Standard',blank:'Blank',sidebar:'With sidebar',starters:'Start from a starter',manual:'Open manual Builder',pending:'Agent is working. Your published page is unchanged.',apply:'Use as starting page',attach:'Attach to Chat',attached:'Attached to Chat',blocks:'blocks'};
export const shellChoices: {id:PageShell;label:string}[] = [{id:'standard',label:onboardingCopy.standard},{id:'blank',label:onboardingCopy.blank},{id:'sidebar',label:onboardingCopy.sidebar}];
export function canOnboard(marked:boolean,published:boolean,count:number){return marked&&!published&&count===0;}
/** the onboarding cards: the starters available on this site (Starters::forEditor) that have blocks, at most three */
export function starterCards(starters:Starter[]){return starters.filter(s=>s.blocks>0&&(s.items?.length??0)>0).slice(0,3);}
/** the Chat attachment of a starter card (the composer shows this JSON; the server resolves the id again from its own
 *  starter file: PageOnboarding::reference) */
export function starterAttachment(starter:Starter,source:string){return {id:starter.id,label:starter.label,json:JSON.stringify({source,starter:starter.id,blocks:starter.items??[]},null,2)};}
