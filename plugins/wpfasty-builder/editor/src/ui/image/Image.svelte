<script lang="ts">
  /** Native kit extension: image semantic, not a composition escape or dynamic tag. */
  let {src,alt='',class:className='',loading='lazy'}:{src:string;alt?:string;class?:string;loading?:'lazy'|'eager'}=$props();
</script>
<img {src} {alt} class={className} {loading} decoding="async" />
