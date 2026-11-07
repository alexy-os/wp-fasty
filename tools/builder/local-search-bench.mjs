/** NON-LIVE, reproducible local retrieval microbenchmark. Not tokens/model latency or Windows acceptance. */
import {readFileSync} from 'node:fs';
import {catalogSession,loadCatalog} from '../../plugins/wpfasty-builder/worker/catalog-search.mjs';
const catalog=JSON.parse(readFileSync(new URL('../../plugins/wpfasty-builder/sources/ui8kit/latte/catalog.json',import.meta.url)));
const before=process.memoryUsage(),cpu=process.cpuUsage(),start=performance.now();
const resource=await loadCatalog();
const session=await catalogSession({source:'ui8kit',target:'latte',catalog:catalog.blocks,records:catalog.records},resource);
const coldMs=performance.now()-start,after=process.memoryUsage();
const query={filters:{job:['hero']},limit:5};
for(let n=0;n<50;n++)session.search(query);
const times=[];for(let n=0;n<1000;n++){const t=performance.now();session.search(query);times.push(performance.now()-t);}times.sort((a,b)=>a-b);
const result=session.search(query),used=process.cpuUsage(cpu);
console.log(JSON.stringify({kind:'NON-LIVE Linux local microbenchmark; not token or model estimates',node:process.version,platform:process.platform,arch:process.arch,iterations:1000,snapshotBytes:resource.manifest.stockBytes,installedBlocks:catalog.blocks.length,installedVariants:session.descriptor.installedVariants,searchableVariants:session.descriptor.searchableVariants,excludedInstalledVariants:session.descriptor.excludedInstalledVariants,excludedStockBlockVariants:session.descriptor.excludedStockBlockVariants,returned:result.results.length,matchedCards:result.total,shortlistBytes:Buffer.byteLength(JSON.stringify(result)),descriptorBytes:Buffer.byteLength(JSON.stringify(session.descriptor)),coldLoadAndScopeMs:+coldMs.toFixed(3),warmSearchMedianMs:+times[500].toFixed(3),warmSearchP95Ms:+times[950].toFixed(3),totalCpuUserMs:used.user/1000,totalCpuSystemMs:used.system/1000,heapDeltaBytes:after.heapUsed-before.heapUsed,rssDeltaBytes:after.rss-before.rss,processPeakRssKiB:process.resourceUsage().maxRSS},null,2));
