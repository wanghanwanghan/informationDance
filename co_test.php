<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateMysqlPoolForJinCai;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class co_test extends AbstractProcess
{
    //启动
    protected function run($arg)
    {
        $list = [
            '137785	1',
            '89101	1',
            '84869	1',
            '63663	1',
            '61567	1',
            '59119	1',
            '54717	1',
            '53289	1',
            '48404	1',
            '41710	1',
            '37630	1',
            '31923	1',
            '29797	1',
            '28035	1',
            '28032	1',
            '28030	1',
            '27753	1',
            '26241	1',
            '25683	1',
            '24883	1',
            '17604	1',
            '16391	1',
            '14579	1',
            '12540	1',
            '12303	1',
            '12272	1',
            '10578	1',
            '10268	1',
            '9693	1',
            '8978	1',
            '8718	1',
            '8580	1',
            '8300	1',
            '8197	1',
            '8185	1',
            '8007	1',
            '7965	1',
            '7947	1',
            '7880	1',
            '7790	1',
            '7774	1',
            '7673	1',
            '7535	1',
            '7349	1',
            '7212	1',
            '7191	1',
            '6992	1',
            '6961	1',
            '6589	1',
            '6559	1',
            '6367	1',
            '6364	1',
            '6217	1',
            '6156	2',
            '5852	1',
            '5790	1',
            '5696	1',
            '5673	1',
            '5418	1',
            '5327	1',
            '5174	1',
            '4955	1',
            '4794	1',
            '4701	1',
            '4692	1',
            '4645	1',
            '4597	1',
            '4592	1',
            '4528	1',
            '4489	1',
            '4448	1',
            '4439	1',
            '4408	1',
            '4299	1',
            '4274	1',
            '4237	1',
            '4136	1',
            '4120	1',
            '4107	1',
            '3859	1',
            '3850	1',
            '3816	1',
            '3808	1',
            '3705	1',
            '3663	1',
            '3659	1',
            '3652	1',
            '3605	1',
            '3577	1',
            '3529	1',
            '3501	1',
            '3497	1',
            '3490	1',
            '3453	1',
            '3418	1',
            '3390	1',
            '3365	1',
            '3349	1',
            '3342	1',
            '3277	1',
            '3232	1',
            '3196	2',
            '3187	1',
            '3177	1',
            '3082	1',
            '3028	1',
            '2981	1',
            '2959	1',
            '2955	1',
            '2923	1',
            '2912	1',
            '2884	1',
            '2868	1',
            '2842	1',
            '2837	1',
            '2828	1',
            '2822	1',
            '2811	1',
            '2807	1',
            '2775	1',
            '2753	1',
            '2724	1',
            '2693	1',
            '2662	1',
            '2658	1',
            '2645	1',
            '2639	1',
            '2625	1',
            '2584	1',
            '2577	1',
            '2500	1',
            '2481	1',
            '2468	1',
            '2460	1',
            '2453	1',
            '2435	1',
            '2429	1',
            '2418	1',
            '2400	1',
            '2392	1',
            '2370	1',
            '2366	1',
            '2355	1',
            '2347	1',
            '2328	1',
            '2310	1',
            '2308	1',
            '2285	1',
            '2266	1',
            '2257	1',
            '2251	1',
            '2243	1',
            '2241	1',
            '2240	1',
            '2214	1',
            '2186	1',
            '2157	1',
            '2151	1',
            '2148	1',
            '2141	1',
            '2125	1',
            '2122	1',
            '2090	1',
            '2087	1',
            '2081	1',
            '2068	1',
            '2056	2',
            '2047	1',
            '2038	1',
            '2025	1',
            '1996	1',
            '1985	1',
            '1984	1',
            '1972	1',
            '1965	1',
            '1952	1',
            '1933	1',
            '1918	1',
            '1915	1',
            '1896	1',
            '1895	1',
            '1894	1',
            '1892	1',
            '1850	1',
            '1841	1',
            '1826	1',
            '1823	3',
            '1815	1',
            '1789	1',
            '1782	1',
            '1777	1',
            '1766	2',
            '1762	1',
            '1754	1',
            '1731	1',
            '1730	1',
            '1705	1',
            '1703	1',
            '1701	1',
            '1698	1',
            '1687	1',
            '1686	1',
            '1677	1',
            '1675	1',
            '1669	1',
            '1668	1',
            '1660	2',
            '1656	1',
            '1647	1',
            '1643	1',
            '1641	1',
            '1619	1',
            '1613	1',
            '1611	1',
            '1607	1',
            '1604	1',
            '1602	1',
            '1591	2',
            '1588	1',
            '1585	2',
            '1582	2',
            '1575	1',
            '1560	1',
            '1554	1',
            '1547	1',
            '1538	1',
            '1536	1',
            '1529	1',
            '1525	1',
            '1519	1',
            '1516	1',
            '1513	1',
            '1511	1',
            '1508	1',
            '1503	1',
            '1495	1',
            '1486	1',
            '1482	1',
            '1481	1',
            '1480	2',
            '1472	1',
            '1469	1',
            '1464	1',
            '1463	1',
            '1461	1',
            '1442	1',
            '1441	1',
            '1440	1',
            '1439	1',
            '1422	1',
            '1419	1',
            '1416	1',
            '1411	2',
            '1408	1',
            '1403	1',
            '1402	1',
            '1392	1',
            '1390	1',
            '1381	1',
            '1380	1',
            '1377	2',
            '1374	1',
            '1373	1',
            '1366	1',
            '1365	2',
            '1363	1',
            '1359	1',
            '1354	1',
            '1353	1',
            '1352	1',
            '1343	1',
            '1342	1',
            '1338	1',
            '1333	1',
            '1330	1',
            '1328	1',
            '1326	1',
            '1318	1',
            '1315	1',
            '1312	2',
            '1311	1',
            '1307	1',
            '1306	2',
            '1304	1',
            '1303	1',
            '1297	1',
            '1292	1',
            '1288	1',
            '1283	1',
            '1282	1',
            '1281	1',
            '1278	1',
            '1277	1',
            '1276	3',
            '1275	2',
            '1274	1',
            '1253	1',
            '1252	1',
            '1251	1',
            '1250	1',
            '1249	2',
            '1248	1',
            '1243	2',
            '1242	2',
            '1240	1',
            '1232	1',
            '1231	1',
            '1230	1',
            '1228	1',
            '1222	1',
            '1217	2',
            '1216	1',
            '1215	1',
            '1205	1',
            '1201	2',
            '1200	1',
            '1193	1',
            '1190	2',
            '1188	1',
            '1187	1',
            '1184	1',
            '1181	2',
            '1180	1',
            '1179	1',
            '1178	1',
            '1172	2',
            '1170	1',
            '1165	2',
            '1164	1',
            '1162	1',
            '1156	1',
            '1154	2',
            '1148	1',
            '1144	1',
            '1143	1',
            '1142	1',
            '1139	1',
            '1134	1',
            '1133	2',
            '1131	1',
            '1130	1',
            '1129	1',
            '1122	1',
            '1118	1',
            '1111	1',
            '1110	1',
            '1105	1',
            '1097	1',
            '1094	1',
            '1091	2',
            '1084	1',
            '1082	1',
            '1081	1',
            '1077	2',
            '1076	2',
            '1068	1',
            '1065	1',
            '1062	2',
            '1059	2',
            '1057	1',
            '1056	1',
            '1050	1',
            '1045	2',
            '1044	1',
            '1036	1',
            '1031	1',
            '1022	1',
            '1021	2',
            '1020	2',
            '1019	1',
            '1016	1',
            '1010	2',
            '1008	1',
            '1007	2',
            '1006	5',
            '1002	1',
            '999	1',
            '998	1',
            '994	2',
            '992	1',
            '990	1',
            '989	1',
            '987	3',
            '986	3',
            '985	1',
            '984	1',
            '982	2',
            '981	1',
            '977	2',
            '976	1',
            '975	1',
            '974	1',
            '973	1',
            '970	1',
            '965	2',
            '962	1',
            '958	1',
            '951	1',
            '950	4',
            '949	1',
            '948	1',
            '947	3',
            '946	1',
            '945	1',
            '944	1',
            '937	2',
            '936	1',
            '934	1',
            '933	2',
            '932	1',
            '929	2',
            '928	1',
            '927	2',
            '925	1',
            '924	3',
            '921	2',
            '920	1',
            '919	1',
            '918	3',
            '917	1',
            '916	1',
            '915	1',
            '914	1',
            '913	1',
            '910	1',
            '909	1',
            '907	1',
            '904	1',
            '901	1',
            '900	1',
            '899	2',
            '898	2',
            '896	2',
            '895	2',
            '892	1',
            '890	1',
            '888	2',
            '884	3',
            '881	1',
            '880	3',
            '879	2',
            '878	1',
            '877	2',
            '875	1',
            '873	4',
            '872	1',
            '871	1',
            '870	2',
            '869	3',
            '868	1',
            '867	4',
            '866	3',
            '864	1',
            '863	1',
            '861	1',
            '858	1',
            '856	2',
            '854	1',
            '853	1',
            '851	1',
            '850	2',
            '849	1',
            '848	1',
            '847	1',
            '842	3',
            '841	2',
            '839	2',
            '837	1',
            '836	1',
            '835	1',
            '834	1',
            '831	1',
            '828	1',
            '827	1',
            '826	1',
            '825	1',
            '823	1',
            '822	1',
            '821	1',
            '820	1',
            '819	2',
            '818	1',
            '816	2',
            '815	2',
            '813	1',
            '811	1',
            '809	4',
            '808	1',
            '807	2',
            '805	1',
            '804	1',
            '803	1',
            '802	2',
            '797	4',
            '789	1',
            '788	1',
            '787	1',
            '785	2',
            '784	1',
            '783	2',
            '781	3',
            '777	1',
            '772	1',
            '771	3',
            '770	3',
            '769	1',
            '768	5',
            '767	2',
            '766	2',
            '765	2',
            '763	3',
            '762	1',
            '761	3',
            '760	1',
            '758	3',
            '757	1',
            '756	1',
            '754	2',
            '753	4',
            '752	1',
            '751	1',
            '750	1',
            '749	3',
            '748	2',
            '747	2',
            '746	3',
            '745	1',
            '742	2',
            '741	1',
            '740	1',
            '738	1',
            '737	2',
            '736	1',
            '734	1',
            '733	1',
            '732	2',
            '731	3',
            '730	2',
            '728	1',
            '727	1',
            '726	3',
            '725	2',
            '724	1',
            '722	1',
            '720	2',
            '719	6',
            '718	1',
            '716	1',
            '715	1',
            '713	3',
            '712	2',
            '711	4',
            '710	4',
            '709	2',
            '708	1',
            '707	1',
            '705	1',
            '704	1',
            '703	1',
            '702	1',
            '700	1',
            '699	3',
            '698	1',
            '696	2',
            '695	3',
            '694	2',
            '693	3',
            '692	2',
            '691	1',
            '690	1',
            '688	1',
            '685	1',
            '684	2',
            '683	4',
            '681	2',
            '679	3',
            '678	1',
            '677	2',
            '676	1',
            '674	3',
            '673	1',
            '671	2',
            '669	2',
            '667	4',
            '665	2',
            '664	1',
            '663	2',
            '662	2',
            '661	2',
            '659	4',
            '657	5',
            '656	1',
            '655	4',
            '654	1',
            '653	2',
            '652	3',
            '650	2',
            '649	3',
            '648	4',
            '647	5',
            '646	3',
            '644	2',
            '643	2',
            '642	3',
            '641	1',
            '640	5',
            '639	2',
            '638	2',
            '637	3',
            '636	1',
            '635	3',
            '634	2',
            '632	2',
            '631	3',
            '630	2',
            '629	1',
            '627	1',
            '626	4',
            '625	2',
            '624	3',
            '623	2',
            '622	3',
            '621	5',
            '620	1',
            '619	4',
            '618	5',
            '617	1',
            '616	4',
            '614	1',
            '613	2',
            '612	4',
            '611	3',
            '608	1',
            '607	2',
            '606	2',
            '604	2',
            '603	5',
            '602	4',
            '601	4',
            '600	1',
            '599	2',
            '598	2',
            '597	1',
            '596	6',
            '595	1',
            '594	2',
            '593	1',
            '592	5',
            '591	2',
            '590	2',
            '589	1',
            '588	1',
            '587	2',
            '586	3',
            '585	4',
            '584	3',
            '583	1',
            '582	2',
            '581	1',
            '580	3',
            '579	1',
            '578	1',
            '577	1',
            '574	2',
            '573	3',
            '572	6',
            '571	2',
            '570	5',
            '569	2',
            '568	3',
            '567	5',
            '566	4',
            '565	2',
            '564	2',
            '563	4',
            '562	5',
            '561	2',
            '560	3',
            '559	4',
            '558	7',
            '557	4',
            '556	4',
            '555	2',
            '554	4',
            '552	4',
            '551	1',
            '550	4',
            '549	3',
            '548	1',
            '547	1',
            '546	3',
            '545	1',
            '544	7',
            '543	4',
            '542	4',
            '541	4',
            '540	3',
            '539	2',
            '538	3',
            '537	4',
            '536	3',
            '535	2',
            '534	3',
            '533	6',
            '532	6',
            '531	3',
            '530	4',
            '529	3',
            '528	5',
            '527	5',
            '526	2',
            '525	1',
            '524	5',
            '522	4',
            '521	3',
            '520	1',
            '519	5',
            '518	2',
            '517	1',
            '516	1',
            '515	6',
            '514	3',
            '513	4',
            '512	4',
            '511	2',
            '510	2',
            '508	2',
            '507	4',
            '506	3',
            '505	4',
            '504	4',
            '503	3',
            '502	6',
            '501	5',
            '500	3',
            '499	2',
            '498	3',
            '497	8',
            '496	4',
            '495	3',
            '494	1',
            '492	7',
            '491	4',
            '490	4',
            '489	8',
            '488	7',
            '487	6',
            '486	4',
            '485	2',
            '484	3',
            '483	2',
            '482	6',
            '481	2',
            '480	2',
            '479	9',
            '478	5',
            '477	2',
            '476	3',
            '475	4',
            '474	2',
            '473	6',
            '472	2',
            '471	7',
            '470	8',
            '469	3',
            '468	5',
            '467	2',
            '466	4',
            '465	4',
            '464	6',
            '463	4',
            '462	6',
            '461	3',
            '460	3',
            '459	5',
            '458	10',
            '457	5',
            '456	3',
            '455	4',
            '454	4',
            '453	8',
            '452	6',
            '451	2',
            '450	5',
            '449	6',
            '448	8',
            '447	3',
            '446	1',
            '445	7',
            '444	3',
            '443	3',
            '442	5',
            '441	6',
            '440	8',
            '439	4',
            '438	9',
            '437	4',
            '436	6',
            '435	4',
            '434	5',
            '433	5',
            '432	5',
            '431	2',
            '430	3',
            '429	2',
            '428	5',
            '427	6',
            '426	9',
            '425	7',
            '424	10',
            '423	13',
            '422	6',
            '421	5',
            '420	4',
            '419	6',
            '418	6',
            '417	4',
            '416	5',
            '415	5',
            '414	2',
            '413	7',
            '412	4',
            '411	5',
            '410	5',
            '409	7',
            '408	6',
            '407	8',
            '406	4',
            '405	5',
            '404	6',
            '403	6',
            '402	6',
            '401	10',
            '400	9',
            '399	9',
            '398	6',
            '397	7',
            '396	7',
            '395	6',
            '394	12',
            '393	7',
            '392	4',
            '391	8',
            '390	7',
            '389	10',
            '388	6',
            '387	7',
            '386	5',
            '385	4',
            '384	5',
            '383	5',
            '382	7',
            '381	8',
            '380	7',
            '379	3',
            '378	5',
            '377	9',
            '376	7',
            '375	9',
            '374	15',
            '373	5',
            '372	9',
            '371	4',
            '370	11',
            '369	6',
            '368	11',
            '367	9',
            '366	11',
            '365	6',
            '364	3',
            '363	10',
            '362	3',
            '361	7',
            '360	9',
            '359	8',
            '358	8',
            '357	6',
            '356	8',
            '355	10',
            '354	6',
            '353	6',
            '352	4',
            '351	10',
            '350	9',
            '349	15',
            '348	9',
            '347	7',
            '346	13',
            '345	11',
            '344	10',
            '343	12',
            '342	11',
            '341	12',
            '340	6',
            '339	15',
            '338	11',
            '337	10',
            '336	12',
            '335	9',
            '334	16',
            '333	14',
            '332	16',
            '331	16',
            '330	15',
            '329	12',
            '328	12',
            '327	11',
            '326	11',
            '325	7',
            '324	7',
            '323	10',
            '322	15',
            '321	8',
            '320	15',
            '319	14',
            '318	13',
            '317	14',
            '316	22',
            '315	12',
            '314	13',
            '313	4',
            '312	11',
            '311	11',
            '310	11',
            '309	20',
            '308	9',
            '307	15',
            '306	16',
            '305	9',
            '304	14',
            '303	14',
            '302	12',
            '301	16',
            '300	18',
            '299	10',
            '298	11',
            '297	16',
            '296	12',
            '295	9',
            '294	14',
            '293	17',
            '292	9',
            '291	14',
            '290	22',
            '289	9',
            '288	15',
            '287	11',
            '286	18',
            '285	12',
            '284	15',
            '283	9',
            '282	14',
            '281	19',
            '280	11',
            '279	16',
            '278	15',
            '277	11',
            '276	13',
            '275	10',
            '274	15',
            '273	19',
            '272	16',
            '271	9',
            '270	8',
            '269	20',
            '268	19',
            '267	13',
            '266	21',
            '265	26',
            '264	22',
            '263	6',
            '262	11',
            '261	22',
            '260	30',
            '259	26',
            '258	20',
            '257	22',
            '256	32',
            '255	31',
            '254	16',
            '253	20',
            '252	18',
            '251	14',
            '250	23',
            '249	20',
            '248	25',
            '247	15',
            '246	26',
            '245	17',
            '244	17',
            '243	22',
            '242	26',
            '241	19',
            '240	20',
            '239	19',
            '238	22',
            '237	35',
            '236	18',
            '235	17',
            '234	30',
            '233	15',
            '232	31',
            '231	25',
            '230	19',
            '229	19',
            '228	21',
            '227	31',
            '226	22',
            '225	35',
            '224	23',
            '223	24',
            '222	33',
            '221	23',
            '220	30',
            '219	27',
            '218	30',
            '217	29',
            '216	28',
            '215	26',
            '214	34',
            '213	21',
            '212	36',
            '211	28',
            '210	37',
            '209	49',
            '208	35',
            '207	40',
            '206	44',
            '205	30',
            '204	32',
            '203	36',
            '202	31',
            '201	38',
            '200	49',
            '199	39',
            '198	33',
            '197	38',
            '196	41',
            '195	37',
            '194	36',
            '193	41',
            '192	39',
            '191	34',
            '190	36',
            '189	38',
            '188	38',
            '187	42',
            '186	37',
            '185	38',
            '184	38',
            '183	36',
            '182	43',
            '181	42',
            '180	45',
            '179	45',
            '178	35',
            '177	38',
            '176	60',
            '175	39',
            '174	64',
            '173	47',
            '172	57',
            '171	43',
            '170	59',
            '169	58',
            '168	71',
            '167	71',
            '166	56',
            '165	53',
            '164	51',
            '163	59',
            '162	71',
            '161	51',
            '160	64',
            '159	57',
            '158	73',
            '157	61',
            '156	58',
            '155	59',
            '154	70',
            '153	54',
            '152	64',
            '151	63',
            '150	73',
            '149	51',
            '148	73',
            '147	63',
            '146	75',
            '145	71',
            '144	80',
            '143	72',
            '142	88',
            '141	84',
            '140	101',
            '139	89',
            '138	104',
            '137	82',
            '136	86',
            '135	78',
            '134	100',
            '133	97',
            '132	105',
            '131	83',
            '130	121',
            '129	74',
            '128	127',
            '127	87',
            '126	124',
            '125	90',
            '124	121',
            '123	106',
            '122	113',
            '121	110',
            '120	144',
            '119	119',
            '118	125',
            '117	115',
            '116	119',
            '115	111',
            '114	140',
            '113	103',
            '112	169',
            '111	128',
            '110	167',
            '109	150',
            '108	154',
            '107	136',
            '106	179',
            '105	169',
            '104	192',
            '103	146',
            '102	205',
            '101	173',
            '100	199',
            '99	190',
            '98	232',
            '97	191',
            '96	207',
            '95	169',
            '94	244',
            '93	206',
            '92	232',
            '91	203',
            '90	266',
            '89	237',
            '88	273',
            '87	224',
            '86	278',
            '85	214',
            '84	326',
            '83	249',
            '82	319',
            '81	275',
            '80	333',
            '79	297',
            '78	424',
            '77	314',
            '76	388',
            '75	301',
            '74	424',
            '73	357',
            '72	411',
            '71	371',
            '70	480',
            '69	393',
            '68	514',
            '67	372',
            '66	590',
            '65	421',
            '64	572',
            '63	451',
            '62	658',
            '61	450',
            '60	681',
            '59	544',
            '58	734',
            '57	560',
            '56	805',
            '55	588',
            '54	873',
            '53	614',
            '52	1001',
            '51	668',
            '50	1080',
            '49	759',
            '48	1119',
            '47	845',
            '46	1186',
            '45	910',
            '44	1330',
            '43	1020',
            '42	1489',
            '41	1090',
            '40	1767',
            '39	1241',
            '38	2002',
            '37	1441',
            '36	2090',
            '35	1535',
            '34	2378',
            '33	1767',
            '32	2795',
            '31	2089',
            '30	3234',
            '29	2229',
            '28	3684',
            '27	2613',
            '26	4203',
            '25	3039',
            '24	5028',
            '23	3444',
            '22	6252',
            '21	4313',
            '20	8321',
            '19	5188',
            '18	9597',
            '17	6298',
            '16	12327',
            '15	7984',
            '14	16421',
            '13	10434',
            '12	22990',
            '11	13958',
            '10	34878',
            '9	20470',
            '8	54781',
            '7	31329',
            '6	104079',
            '5	58343',
            '4	253001',
            '3	139050',
            '2	1135290',
            '1	746854',
        ];

        foreach ($list as $one) {

            $arr = explode("\t", $one);

            $num1 = $arr[0] - 0;
            $num2 = $arr[1] - 0;

            if ($num1 <= 3) {

            } elseif ($num1) {

            } elseif () {

            } elseif () {

            } elseif () {

            } elseif () {

            } elseif () {

            } else {

            }


        }

    }

    function do_strtr(?string $str): string
    {
        $str = str_replace(["\r\n", "\r", "\n", '|', "\t"], '', trim($str));

        return strtr($str, [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ]);
    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {

    }

}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);

//mysql pool
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlPoolForRDS3NicCode::getInstance()->createMysql();
CreateMysqlPoolForRDS3SiJiFenLei::getInstance()->createMysql();
CreateMysqlPoolForJinCai::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();
CreateMysqlOrm::getInstance()->createRDS3JinCai();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new co_test($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    exit;
}
