<script lang="ts">
  // Onboarding cards of a new empty page, in place of the removed template screens (ADR-007): the Simple starters
  // available on this site, each drawn as live prototypes of its first blocks (the same sandboxed, script-free
  // prototype documents as the block cards). With the optional Chat module the card attaches the starter's JSON to Chat
  // (as the screen cards did); without it the card applies the starter as the starting composition. "Use as starting
  // page" always applies it. No model call happens here.
  import Box from './ui/box/Box.svelte';
  import Button from './ui/button/Button.svelte';
  import Text from './ui/text/Text.svelte';
  import type {Starter} from './types';
  import {starterCards,onboardingCopy as copy} from './onboarding';
  import {prototypeSource} from './prototype';
  let {starters,previewUrl='',dark=false,chat=false,attached=null,busy=false,onapply,onattach}:{
    starters:Starter[]; previewUrl?:string; dark?:boolean; chat?:boolean; attached?:string|null; busy?:boolean;
    onapply:(starter:Starter)=>void; onattach:(starter:Starter)=>void;
  }=$props();
  const cards=$derived(starterCards(starters));
  /** the prototypes are 1024px wide documents scaled to the card */
  const fit=(el:HTMLElement)=>{const ro=new ResizeObserver(([e])=>el.style.setProperty('--wpfb-proto-scale',String(e!.contentRect.width/1024)));ro.observe(el);return()=>ro.disconnect();};
</script>
<Box tag="section" class="wpfb-onboarding-screens" aria-label={copy.starters}>
{#each cards as starter(starter.id)}
<Box tag="article" class="wpfb-screen-card">
<Button class="wpfb-screen-select" disabled={busy} aria-pressed={chat?(attached===starter.id?'true':'false'):undefined}
  onclick={()=>chat?onattach(starter):onapply(starter)}>
<Box tag="span" class="wpfb-screen-media" aria-hidden="true" {@attach fit}>
{#if previewUrl}
{#each (starter.items??[]).slice(0,2) as item,i(i)}
<iframe class="wpfb-screen-prototype" src={prototypeSource(previewUrl,item.block,item.variant,dark)} title="" sandbox="allow-same-origin" loading="lazy" tabindex="-1" width="1024" height="576"></iframe>
{/each}
{/if}
</Box>
<Text tag="strong">{starter.label}</Text>
<Text tag="span" class="wpfb-screen-docs">{starter.description}</Text>
<Text tag="span" class="wpfb-screen-docs">{`${starter.blocks} ${copy.blocks} · ${chat?(attached===starter.id?copy.attached:copy.attach):copy.apply}`}</Text>
</Button>
{#if chat}<Button class="button wpfb-screen-apply" disabled={busy} onclick={()=>onapply(starter)}>{copy.apply}</Button>{/if}
</Box>
{/each}
</Box>
