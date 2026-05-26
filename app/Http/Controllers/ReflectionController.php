<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReflectionController extends Controller
{
    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const ISLAMIC_APP_VERSES = 'https://api.islamic.app/v1/verses/by_key/%d:%d';
    private const ISLAMIC_APP_SEARCH = 'https://api.islamic.app/v1/search';

    private array $fallbackVerseMap = [
        'loneliness' => [
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 9, 'ayah' => 40],
            ['surah' => 50, 'ayah' => 16],
            ['surah' => 20, 'ayah' => 46],
            ['surah' => 28, 'ayah' => 7],
        ],
        'anxiety' => [
            ['surah' => 13, 'ayah' => 28],
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
            ['surah' => 29, 'ayah' => 69],
            ['surah' => 16, 'ayah' => 128],
            ['surah' => 8, 'ayah' => 2],
        ],
        'sadness' => [
            ['surah' => 2, 'ayah' => 286],
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 12, 'ayah' => 87],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
            ['surah' => 4, 'ayah' => 19],
            ['surah' => 16, 'ayah' => 97],
            ['surah' => 3, 'ayah' => 139],
        ],
        'happiness' => [
            ['surah' => 10, 'ayah' => 58],
            ['surah' => 27, 'ayah' => 19],
            ['surah' => 30, 'ayah' => 4],
            ['surah' => 3, 'ayah' => 139],
            ['surah' => 15, 'ayah' => 98],
        ],
        'gratitude' => [
            ['surah' => 14, 'ayah' => 7],
            ['surah' => 55, 'ayah' => 13],
            ['surah' => 16, 'ayah' => 18],
            ['surah' => 2, 'ayah' => 152],
            ['surah' => 31, 'ayah' => 12],
        ],
        'hope' => [
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 29, 'ayah' => 69],
            ['surah' => 94, 'ayah' => 5],
        ],
        'contentment' => [
            ['surah' => 13, 'ayah' => 28],
            ['surah' => 89, 'ayah' => 27],
            ['surah' => 89, 'ayah' => 28],
            ['surah' => 48, 'ayah' => 4],
            ['surah' => 16, 'ayah' => 97],
        ],
        'financial_worry' => [
            ['surah' => 65, 'ayah' => 2],
            ['surah' => 65, 'ayah' => 3],
            ['surah' => 11, 'ayah' => 6],
            ['surah' => 51, 'ayah' => 58],
            ['surah' => 2, 'ayah' => 261],
            ['surah' => 67, 'ayah' => 15],
        ],
        'grief' => [
            ['surah' => 2, 'ayah' => 156],
            ['surah' => 2, 'ayah' => 157],
            ['surah' => 2, 'ayah' => 286],
            ['surah' => 16, 'ayah' => 127],
            ['surah' => 94, 'ayah' => 5],
            ['surah' => 94, 'ayah' => 6],
        ],
        'guilt' => [
            ['surah' => 39, 'ayah' => 53],
            ['surah' => 42, 'ayah' => 25],
            ['surah' => 66, 'ayah' => 8],
            ['surah' => 25, 'ayah' => 70],
            ['surah' => 9, 'ayah' => 104],
        ],
        'anger' => [
            ['surah' => 3, 'ayah' => 134],
            ['surah' => 42, 'ayah' => 37],
            ['surah' => 42, 'ayah' => 40],
            ['surah' => 25, 'ayah' => 63],
            ['surah' => 7, 'ayah' => 199],
        ],
        'yearning' => [
            ['surah' => 2, 'ayah' => 186],
            ['surah' => 28, 'ayah' => 24],
            ['surah' => 25, 'ayah' => 74],
            ['surah' => 30, 'ayah' => 21],
            ['surah' => 24, 'ayah' => 32],
            ['surah' => 93, 'ayah' => 3],
        ],
    ];

    private array $crisisFallback = [
        ['surah' => 2, 'ayah' => 195],
        ['surah' => 4, 'ayah' => 29],
        ['surah' => 39, 'ayah' => 53],
        ['surah' => 12, 'ayah' => 87],
        ['surah' => 94, 'ayah' => 5],
        ['surah' => 94, 'ayah' => 6],
    ];

    private array $localVerseTexts = [
        '2:186' => ['en' => 'And when My servants ask you, [O Muḥammad], concerning Me - indeed I am near. I respond to the invocation of the supplicant when he calls upon Me. So let them respond to Me [by obedience] and believe in Me that they may be [rightly] guided.', 'ar' => 'وَإِذَا سَأَلَكَ عِبَادِى عَنِّى فَإِنِّى قَرِيبٌ ۖ أُجِيبُ دَعْوَةَ ٱلدَّاعِ إِذَا دَعَانِ ۖ فَلْيَسْتَجِيبُوا۟ لِى وَلْيُؤْمِنُوا۟ بِى لَعَلَّهُمْ يَرْشُدُونَ'],
        '9:40' => ['en' => 'If you do not aid him - Allāh has already aided him when those who disbelieved had driven him out as one of two, when they were in the cave and he said to his companion, "Do not grieve; indeed Allāh is with us." And Allāh sent down His tranquility upon him and supported him with soldiers you did not see and made the word of those who disbelieved the lowest, while the word of Allāh - that is the highest. And Allāh is Exalted in Might and Wise.', 'ar' => 'إِلَّا تَنصُرُوهُ فَقَدْ نَصَرَهُ ٱللَّهُ إِذْ أَخْرَجَهُ ٱلَّذِينَ كَفَرُوا۟ ثَانِىَ ٱثْنَيْنِ إِذْ هُمَا فِى ٱلْغَارِ إِذْ يَقُولُ لِصَـٰحِبِهِۦ لَا تَحْزَنْ إِنَّ ٱللَّهَ مَعَنَا ۖ فَأَنزَلَ ٱللَّهُ سَكِينَتَهُۥ عَلَيْهِ وَأَيَّدَهُۥ بِجُنُودٍ لَّمْ تَرَوْهَا وَجَعَلَ كَلِمَةَ ٱلَّذِينَ كَفَرُوا۟ ٱلسُّفْلَىٰ ۗ وَكَلِمَةُ ٱللَّهِ هِىَ ٱلْعُلْيَا ۗ وَٱللَّهُ عَزِيزٌ حَكِيمٌ'],
        '50:16' => ['en' => 'And We have already created man and know what his soul whispers to him, and We are closer to him than his jugular vein.', 'ar' => 'وَلَقَدْ خَلَقْنَا ٱلْإِنسَـٰنَ وَنَعْلَمُ مَا تُوَسْوِسُ بِهِۦ نَفْسُهُۥ ۖ وَنَحْنُ أَقْرَبُ إِلَيْهِ مِنْ حَبْلِ ٱلْوَرِيدِ'],
        '20:46' => ['en' => '[Allāh] said, "Fear not. Indeed, I am with you both; I hear and I see.', 'ar' => 'قَالَ لَا تَخَافَآ ۖ إِنَّنِى مَعَكُمَآ أَسْمَعُ وَأَرَىٰ'],
        '28:7' => ['en' => 'And We inspired to the mother of Moses, "Suckle him; but when you fear for him, cast him into the river and do not fear and do not grieve. Indeed, We will return him to you and will make him one of the messengers."', 'ar' => 'وَأَوْحَيْنَآ إِلَىٰٓ أُمِّ مُوسَىٰٓ أَنْ أَرْضِعِيهِ ۖ فَإِذَا خِفْتِ عَلَيْهِ فَأَلْقِيهِ فِى ٱلْيَمِّ وَلَا تَخَافِى وَلَا تَحْزَنِىٓ ۖ إِنَّا رَآدُّوهُ إِلَيْكِ وَجَاعِلُوهُ مِنَ ٱلْمُرْسَلِينَ'],
        '13:28' => ['en' => 'Those who have believed and whose hearts are assured by the remembrance of Allāh. Unquestionably, by the remembrance of Allāh hearts are assured."', 'ar' => 'ٱلَّذِينَ ءَامَنُوا۟ وَتَطْمَئِنُّ قُلُوبُهُم بِذِكْرِ ٱللَّهِ ۗ أَلَا بِذِكْرِ ٱللَّهِ تَطْمَئِنُّ ٱلْقُلُوبُ'],
        '65:2' => ['en' => 'And whoever fears Allāh - He will make for him a way out', 'ar' => 'وَمَن يَتَّقِ ٱللَّهَ يَجْعَل لَّهُۥ مَخْرَجًا'],
        '65:3' => ['en' => 'And will provide for him from where he does not expect. And whoever relies upon Allāh - then He is sufficient for him. Indeed, Allāh will accomplish His purpose. Allāh has already set for everything a decreed extent.', 'ar' => 'وَيَرْزُقْهُ مِنْ حَيْثُ لَا يَحْتَسِبُ ۚ وَمَن يَتَوَكَّلْ عَلَى ٱللَّهِ فَهُوَ حَسْبُهُۥٓ ۚ إِنَّ ٱللَّهَ بَـٰلِغُ أَمْرِهِۦ ۚ قَدْ جَعَلَ ٱللَّهُ لِكُلِّ شَىْءٍ قَدْرًا'],
        '94:5' => ['en' => 'For indeed, with hardship will be ease.', 'ar' => 'فَإِنَّ مَعَ ٱلْعُسْرِ يُسْرًا'],
        '94:6' => ['en' => 'Indeed, with hardship will be ease.', 'ar' => 'إِنَّ مَعَ ٱلْعُسْرِ يُسْرًا'],
        '29:69' => ['en' => 'And those who strive for Us - We will surely guide them to Our ways. And indeed, Allāh is with the doers of good.', 'ar' => 'وَٱلَّذِينَ جَـٰهَدُوا۟ فِينَا لَنَهْدِيَنَّهُمْ سُبُلَنَا ۚ وَإِنَّ ٱللَّهَ لَمَعَ ٱلْمُحْسِنِينَ'],
        '16:128' => ['en' => 'Indeed, Allāh is with those who fear Him and those who are doers of good.', 'ar' => 'إِنَّ ٱللَّهَ مَعَ ٱلَّذِينَ ٱتَّقَوا۟ وَّٱلَّذِينَ هُم مُّحْسِنُونَ'],
        '8:2' => ['en' => 'The believers are only those who, when Allāh is mentioned, their hearts become fearful, and when His verses are recited to them, it increases them in faith; and upon their Lord they rely -', 'ar' => 'إِنَّمَا ٱلْمُؤْمِنُونَ ٱلَّذِينَ إِذَا ذُكِرَ ٱللَّهُ وَجِلَتْ قُلُوبُهُمْ وَإِذَا تُلِيَتْ عَلَيْهِمْ ءَايَـٰتُهُۥ زَادَتْهُمْ إِيمَـٰنًا وَعَلَىٰ رَبِّهِمْ يَتَوَكَّلُونَ'],
        '2:286' => ['en' => 'Allāh does not charge a soul except with that within its capacity. It will have what good it has gained, and it will bear what evil it has earned. "Our Lord, do not impose blame upon us if we have forgotten or erred. Our Lord, and lay not upon us a burden like that which You laid upon those before us. Our Lord, and burden us not with that which we have no ability to bear. And pardon us; and forgive us; and have mercy upon us. You are our protector, so give us victory over the disbelieving people."', 'ar' => 'لَا يُكَلِّفُ ٱللَّهُ نَفْسًا إِلَّا وُسْعَهَا ۚ لَهَا مَا كَسَبَتْ وَعَلَيْهَا مَا ٱكْتَسَبَتْ ۗ رَبَّنَا لَا تُؤَاخِذْنَآ إِن نَّسِينَآ أَوْ أَخْطَأْنَا ۚ رَبَّنَا وَلَا تَحْمِلْ عَلَيْنَآ إِصْرًا كَمَا حَمَلْتَهُۥ عَلَى ٱلَّذِينَ مِن قَبْلِنَا ۚ رَبَّنَا وَلَا تُحَمِّلْنَا مَا لَا طَاقَةَ لَنَا بِهِۦ ۖ وَٱعْفُ عَنَّا وَٱغْفِرْ لَنَا وَٱرْحَمْنَآ ۚ أَنتَ مَوْلَىٰنَا فَٱنصُرْنَا عَلَى ٱلْقَوْمِ ٱلْكَـٰفِرِينَ'],
        '39:53' => ['en' => 'Say, "O My servants who have transgressed against themselves by sinning, do not despair of the mercy of Allāh. Indeed, Allāh forgives all sins. Indeed, it is He who is the Forgiving, the Merciful."', 'ar' => '۞ قُلْ يَـٰعِبَادِىَ ٱلَّذِينَ أَسْرَفُوا۟ عَلَىٰٓ أَنفُسِهِمْ لَا تَقْنَطُوا۟ مِن رَّحْمَةِ ٱللَّهِ ۚ إِنَّ ٱللَّهَ يَغْفِرُ ٱلذُّنُوبَ جَمِيعًا ۚ إِنَّهُۥ هُوَ ٱلْغَفُورُ ٱلرَّحِيمُ'],
        '12:87' => ['en' => 'O my sons, go and find out about Joseph and his brother and despair not of relief from Allāh. Indeed, no one despairs of relief from Allāh except the disbelieving people."', 'ar' => 'يَـٰبَنِىَّ ٱذْهَبُوا۟ فَتَحَسَّسُوا۟ مِن يُوسُفَ وَأَخِيهِ وَلَا تَا۟يْـَٔسُوا۟ مِن رَّوْحِ ٱللَّهِ ۖ إِنَّهُۥ لَا يَا۟يْـَٔسُ مِن رَّوْحِ ٱللَّهِ إِلَّا ٱلْقَوْمُ ٱلْكَـٰفِرُونَ'],
        '4:19' => ['en' => 'O you who have believed, it is not lawful for you to inherit women by compulsion. And do not make difficulties for them in order to take back part of what you gave them unless they commit a clear immorality. And live with them in kindness. For if you dislike them - perhaps you dislike a thing and Allāh makes therein much good.', 'ar' => 'يَـٰٓأَيُّهَا ٱلَّذِينَ ءَامَنُوا۟ لَا يَحِلُّ لَكُمْ أَن تَرِثُوا۟ ٱلنِّسَآءَ كَرْهًا ۖ وَلَا تَعْضُلُوهُنَّ لِتَذْهَبُوا۟ بِبَعْضِ مَآ ءَاتَيْتُمُوهُنَّ إِلَّآ أَن يَأْتِينَ بِفَـٰحِشَةٍ مُّبَيِّنَةٍ ۚ وَعَاشِرُوهُنَّ بِٱلْمَعْرُوفِ ۚ فَإِن كَرِهْتُمُوهُنَّ فَعَسَىٰٓ أَن تَكْرَهُوا۟ شَيْـًٔا وَيَجْعَلَ ٱللَّهُ فِيهِ خَيْرًا كَثِيرًا'],
        '16:97' => ['en' => 'Whoever does righteousness, whether male or female, while he is a believer - We will surely cause him to live a good life, and We will surely give them their reward in the Hereafter according to the best of what they used to do.', 'ar' => 'مَنْ عَمِلَ صَـٰلِحًا مِّن ذَكَرٍ أَوْ أُنثَىٰ وَهُوَ مُؤْمِنٌ فَلَنُحْيِيَنَّهُۥ حَيَوٰةً طَيِّبَةً ۖ وَلَنَجْزِيَنَّهُمْ أَجْرَهُم بِأَحْسَنِ مَا كَانُوا۟ يَعْمَلُونَ'],
        '3:139' => ['en' => 'So do not weaken and do not grieve, and you will be superior if you are true believers.', 'ar' => 'وَلَا تَهِنُوا۟ وَلَا تَحْزَنُوا۟ وَأَنتُمُ ٱلْأَعْلَوْنَ إِن كُنتُم مُّؤْمِنِينَ'],
        '10:58' => ['en' => 'Say, "In the bounty of Allāh and in His mercy - in that let them rejoice; it is better than what they accumulate."', 'ar' => 'قُلْ بِفَضْلِ ٱللَّهِ وَبِرَحْمَتِهِۦ فَبِذَٰلِكَ فَلْيَفْرَحُوا۟ هُوَ خَيْرٌ مِّمَّا يَجْمَعُونَ'],
        '27:19' => ['en' => 'So Solomon smiled, amused at her speech, and said, "My Lord, enable me to be grateful for Your favor which You have bestowed upon me and upon my parents and to do righteousness of which You approve. And admit me by Your mercy into the ranks of Your righteous servants."', 'ar' => 'فَتَبَسَّمَ ضَاحِكًا مِّن قَوْلِهَا وَقَالَ رَبِّ أَوْزِعْنِىٓ أَنْ أَشْكُرَ نِعْمَتَكَ ٱلَّتِىٓ أَنْعَمْتَ عَلَىَّ وَعَلَىٰ وَٰلِدَىَّ وَأَنْ أَعْمَلَ صَـٰلِحًا تَرْضَىٰهُ وَأَدْخِلْنِى بِرَحْمَتِكَ فِى عِبَادِكَ ٱلصَّـٰلِحِينَ'],
        '30:4' => ['en' => 'Within three to nine years. To Allāh belongs the command before and after. And that day the believers will rejoice', 'ar' => 'فِى بِضْعِ سِنِينَ ۗ لِلَّهِ ٱلْأَمْرُ مِن قَبْلُ وَمِنۢ بَعْدُ ۚ وَيَوْمَئِذٍ يَفْرَحُ ٱلْمُؤْمِنُونَ'],
        '15:98' => ['en' => 'So exalt Allāh with praise of your Lord and be of those who prostrate to Him.', 'ar' => 'فَسَبِّحْ بِحَمْدِ رَبِّكَ وَكُن مِّنَ ٱلسَّـٰجِدِينَ'],
        '14:7' => ['en' => 'And remember when your Lord proclaimed, "If you are grateful, I will surely increase you in favor; but if you deny, indeed, My punishment is severe."', 'ar' => 'وَإِذْ تَأَذَّنَ رَبُّكُمْ لَئِن شَكَرْتُمْ لَأَزِيدَنَّكُمْ ۖ وَلَئِن كَفَرْتُمْ إِنَّ عَذَابِى لَشَدِيدٌ'],
        '55:13' => ['en' => 'So which of the favors of your Lord would you deny?', 'ar' => 'فَبِأَىِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ'],
        '16:18' => ['en' => 'And if you should count the favors of Allāh, you could not enumerate them. Indeed, Allāh is Forgiving and Merciful.', 'ar' => 'وَإِن تَعُدُّوا۟ نِعْمَةَ ٱللَّهِ لَا تُحْصُوهَآ ۗ إِنَّ ٱللَّهَ لَغَفُورٌ رَّحِيمٌ'],
        '2:152' => ['en' => 'So remember Me; I will remember you. And be grateful to Me and do not deny Me.', 'ar' => 'فَٱذْكُرُونِىٓ أَذْكُرْكُمْ وَٱشْكُرُوا۟ لِى وَلَا تَكْفُرُونِ'],
        '31:12' => ['en' => 'And We had certainly given Luqmān wisdom and said, "Be grateful to Allāh." And whoever is grateful is grateful for the benefit of himself. And whoever denies His favor - then indeed, Allāh is Free of need and Praiseworthy.', 'ar' => 'وَلَقَدْ ءَاتَيْنَا لُقْمَـٰنَ ٱلْحِكْمَةَ أَنِ ٱشْكُرْ لِلَّهِ ۚ وَمَن يَشْكُرْ فَإِنَّمَا يَشْكُرُ لِنَفْسِهِۦ ۖ وَمَن كَفَرَ فَإِنَّ ٱللَّهَ غَنِىٌّ حَمِيدٌ'],
        '89:27' => ['en' => 'To the righteous it will be said, "O reassured soul,', 'ar' => 'يَـٰٓأَيَّتُهَا ٱلنَّفْسُ ٱلْمُطْمَئِنَّةُ'],
        '89:28' => ['en' => 'Return to your Lord, well-pleased and pleasing to Him,', 'ar' => 'ٱرْجِعِىٓ إِلَىٰ رَبِّكِ رَاضِيَةً مَّرْضِيَّةً'],
        '48:4' => ['en' => 'It is He who sent down tranquility into the hearts of the believers that they would increase in faith along with their present faith. And to Allāh belong the soldiers of the heavens and the earth, and ever is Allāh Knowing and Wise.', 'ar' => 'هُوَ ٱلَّذِىٓ أَنزَلَ ٱلسَّكِينَةَ فِى قُلُوبِ ٱلْمُؤْمِنِينَ لِيَزْدَادُوٓا۟ إِيمَـٰنًا مَّعَ إِيمَـٰنِهِمْ ۗ وَلِلَّهِ جُنُودُ ٱلسَّمَـٰوَٰتِ وَٱلْأَرْضِ ۚ وَكَانَ ٱللَّهُ عَلِيمًا حَكِيمًا'],
        '11:6' => ['en' => 'And there is no creature on earth but that upon Allāh is its provision, and He knows its place of dwelling and place of storage. All is in a clear register.', 'ar' => '۞ وَمَا مِن دَآبَّةٍ فِى ٱلْأَرْضِ إِلَّا عَلَى ٱللَّهِ رِزْقُهَا وَيَعْلَمُ مُسْتَقَرَّهَا وَمُسْتَوْدَعَهَا ۚ كُلٌّ فِى كِتَـٰبٍ مُّبِينٍ'],
        '51:58' => ['en' => 'Indeed, it is Allāh who is the continual Provider, the firm possessor of strength.', 'ar' => 'إِنَّ ٱللَّهَ هُوَ ٱلرَّزَّاقُ ذُو ٱلْقُوَّةِ ٱلْمَتِينُ'],
        '2:261' => ['en' => 'The example of those who spend their wealth in the way of Allāh is like a seed of grain which grows seven spikes; in each spike is a hundred grains. And Allāh multiplies His reward for whom He wills. And Allāh is all-Encompassing and Knowing.', 'ar' => 'مَّثَلُ ٱلَّذِينَ يُنفِقُونَ أَمْوَٰلَهُمْ فِى سَبِيلِ ٱللَّهِ كَمَثَلِ حَبَّةٍ أَنۢبَتَتْ سَبْعَ سَنَابِلَ فِى كُلِّ سُنۢبُلَةٍ مِّا۟ئَةُ حَبَّةٍ ۗ وَٱللَّهُ يُضَـٰعِفُ لِمَن يَشَآءُ ۗ وَٱللَّهُ وَٰسِعٌ عَلِيمٌ'],
        '67:15' => ['en' => 'It is He who made the earth tame for you - so walk among its slopes and eat of His provision - and to Him is the resurrection.', 'ar' => 'هُوَ ٱلَّذِى جَعَلَ لَكُمُ ٱلْأَرْضَ ذَلُولًا فَٱمْشُوا۟ فِى مَنَاكِبِهَا وَكُلُوا۟ مِن رِّزْقِهِۦ ۖ وَإِلَيْهِ ٱلنُّشُورُ'],
        '2:156' => ['en' => 'Who, when disaster strikes them, say, "Indeed we belong to Allāh, and indeed to Him we will return."', 'ar' => 'ٱلَّذِينَ إِذَآ أَصَـٰبَتْهُم مُّصِيبَةٌ قَالُوٓا۟ إِنَّا لِلَّهِ وَإِنَّآ إِلَيْهِ رَٰجِعُونَ'],
        '2:157' => ['en' => 'Those are the ones upon whom are blessings from their Lord and mercy. And it is those who are the rightly guided.', 'ar' => 'أُو۟لَـٰٓئِكَ عَلَيْهِمْ صَلَوَٰتٌ مِّن رَّبِّهِمْ وَرَحْمَةٌ ۖ وَأُو۟لَـٰٓئِكَ هُمُ ٱلْمُهْتَدُونَ'],
        '16:127' => ['en' => 'And be patient, and your patience is not but through Allāh. And do not grieve over them and do not be in distress over what they conspire.', 'ar' => 'وَٱصْبِرْ وَمَا صَبْرُكَ إِلَّا بِٱللَّهِ ۚ وَلَا تَحْزَنْ عَلَيْهِمْ وَلَا تَكُ فِى ضَيْقٍ مِّمَّا يَمْكُرُونَ'],
        '42:25' => ['en' => 'And it is He who accepts repentance from His servants and pardons misdeeds, and He knows what you do.', 'ar' => 'وَهُوَ ٱلَّذِى يَقْبَلُ ٱلتَّوْبَةَ عَنْ عِبَادِهِۦ وَيَعْفُوا۟ عَنِ ٱلسَّيِّـَٔاتِ وَيَعْلَمُ مَا تَفْعَلُونَ'],
        '66:8' => ['en' => 'O you who have believed, repent to Allāh with sincere repentance. Perhaps your Lord will remove from you your misdeeds and admit you into gardens beneath which rivers flow on the Day when Allāh will not disgrace the Prophet and those who believed with him. Their light will proceed before them and on their right; they will say, "Our Lord, perfect for us our light and forgive us. Indeed, You are over all things competent."', 'ar' => 'يَـٰٓأَيُّهَا ٱلَّذِينَ ءَامَنُوا۟ تُوبُوٓا۟ إِلَى ٱللَّهِ تَوْبَةً نَّصُوحًا عَسَىٰ رَبُّكُمْ أَن يُكَفِّرَ عَنكُمْ سَيِّـَٔاتِكُمْ وَيُدْخِلَكُمْ جَنَّـٰتٍ تَجْرِى مِن تَحْتِهَا ٱلْأَنْهَـٰرُ يَوْمَ لَا يُخْزِى ٱللَّهُ ٱلنَّبِىَّ وَٱلَّذِينَ ءَامَنُوا۟ مَعَهُۥ ۖ نُورُهُمْ يَسْعَىٰ بَيْنَ أَيْدِيهِمْ وَبِأَيْمَـٰنِهِمْ يَقُولُونَ رَبَّنَآ أَتْمِمْ لَنَا نُورَنَا وَٱغْفِرْ لَنَآ ۖ إِنَّكَ عَلَىٰ كُلِّ شَىْءٍ قَدِيرٌ'],
        '25:70' => ['en' => 'Except for those who repent, believe and do righteous work. For them Allāh will replace their evil deeds with good. And ever is Allāh Forgiving and Merciful.', 'ar' => 'إِلَّا مَن تَابَ وَءَامَنَ وَعَمِلَ عَمَلًا صَـٰلِحًا فَأُو۟لَـٰٓئِكَ يُبَدِّلُ ٱللَّهُ سَيِّـَٔاتِهِمْ حَسَنَـٰتٍ ۗ وَكَانَ ٱللَّهُ غَفُورًا رَّحِيمًا'],
        '9:104' => ['en' => 'Do they not know that it is Allāh who accepts repentance from His servants and receives charities and that it is Allāh who is the Accepting of Repentance, the Merciful?', 'ar' => 'أَلَمْ يَعْلَمُوٓا۟ أَنَّ ٱللَّهَ هُوَ يَقْبَلُ ٱلتَّوْبَةَ عَنْ عِبَادِهِۦ وَيَأْخُذُ ٱلصَّدَقَـٰتِ وَأَنَّ ٱللَّهَ هُوَ ٱلتَّوَّابُ ٱلرَّحِيمُ'],
        '3:134' => ['en' => 'Who spend in the cause of Allāh during ease and hardship and who restrain anger and who pardon the people - and Allāh loves the doers of good;', 'ar' => 'ٱلَّذِينَ يُنفِقُونَ فِى ٱلسَّرَّآءِ وَٱلضَّرَّآءِ وَٱلْكَـٰظِمِينَ ٱلْغَيْظَ وَٱلْعَافِينَ عَنِ ٱلنَّاسِ ۗ وَٱللَّهُ يُحِبُّ ٱلْمُحْسِنِينَ'],
        '42:37' => ['en' => 'And those who avoid the major sins and immoralities, and when they are angry, they forgive,', 'ar' => 'وَٱلَّذِينَ يَجْتَنِبُونَ كَبَـٰٓئِرَ ٱلْإِثْمِ وَٱلْفَوَٰحِشَ وَإِذَا مَا غَضِبُوا۟ هُمْ يَغْفِرُونَ'],
        '42:40' => ['en' => 'And the retribution for an evil act is an evil one like it, but whoever pardons and makes reconciliation - his reward is due from Allāh. Indeed, He does not like wrongdoers.', 'ar' => 'وَجَزَٰٓؤُا۟ سَيِّئَةٍ سَيِّئَةٌ مِّثْلُهَا ۖ فَمَنْ عَفَا وَأَصْلَحَ فَأَجْرُهُۥ عَلَى ٱللَّهِ ۚ إِنَّهُۥ لَا يُحِبُّ ٱلظَّـٰلِمِينَ'],
        '25:63' => ['en' => 'And the servants of the Most Merciful are those who walk upon the earth easily, and when the ignorant address them harshly, they say words of peace,', 'ar' => 'وَعِبَادُ ٱلرَّحْمَـٰنِ ٱلَّذِينَ يَمْشُونَ عَلَى ٱلْأَرْضِ هَوْنًا وَإِذَا خَاطَبَهُمُ ٱلْجَـٰهِلُونَ قَالُوا۟ سَلَـٰمًا'],
        '7:199' => ['en' => 'Take what is given freely, enjoin what is good, and turn away from the ignorant.', 'ar' => 'خُذِ ٱلْعَفْوَ وَأْمُرْ بِٱلْعُرْفِ وَأَعْرِضْ عَنِ ٱلْجَـٰهِلِينَ'],
        '28:24' => ['en' => 'So he watered their flocks for them; then he went back to the shade and said, "My Lord, indeed I am, for whatever good You would send down to me, in need."', 'ar' => 'فَسَقَىٰ لَهُمَا ثُمَّ تَوَلَّىٰٓ إِلَى ٱلظِّلِّ فَقَالَ رَبِّ إِنِّى لِمَآ أَنزَلْتَ إِلَىَّ مِنْ خَيْرٍ فَقِيرٌ'],
        '25:74' => ['en' => 'And those who say, "Our Lord, grant us from among our wives and offspring comfort to our eyes and make us a leader for the righteous."', 'ar' => 'وَٱلَّذِينَ يَقُولُونَ رَبَّنَا هَبْ لَنَا مِنْ أَزْوَٰجِنَا وَذُرِّيَّـٰتِنَا قُرَّةَ أَعْيُنٍ وَٱجْعَلْنَا لِلْمُتَّقِينَ إِمَامًا'],
        '30:21' => ['en' => 'And of His signs is that He created for you from yourselves mates that you may find tranquility in them; and He placed between you affection and mercy. Indeed in that are signs for a people who give thought.', 'ar' => 'وَمِنْ ءَايَـٰتِهِۦٓ أَنْ خَلَقَ لَكُم مِّنْ أَنفُسِكُمْ أَزْوَٰجًا لِّتَسْكُنُوٓا۟ إِلَيْهَا وَجَعَلَ بَيْنَكُم مَّوَدَّةً وَرَحْمَةً ۚ إِنَّ فِى ذَٰلِكَ لَـَٔايَـٰتٍ لِّقَوْمٍ يَتَفَكَّرُونَ'],
        '24:32' => ['en' => 'And marry the unmarried among you and the righteous among your male slaves and female slaves. If they should be poor, Allāh will enrich them from His bounty, and Allāh is all-Encompassing and Knowing.', 'ar' => 'وَأَنكِحُوا۟ ٱلْأَيَـٰمَىٰ مِنكُمْ وَٱلصَّـٰلِحِينَ مِنْ عِبَادِكُمْ وَإِمَآئِكُمْ ۚ إِن يَكُونُوا۟ فُقَرَآءَ يُغْنِهِمُ ٱللَّهُ مِن فَضْلِهِۦ ۗ وَٱللَّهُ وَٰسِعٌ عَلِيمٌ'],
        '93:3' => ['en' => 'Your Lord has not taken leave of you, O Muḥammad, nor has He detested you.', 'ar' => 'مَا وَدَّعَكَ رَبُّكَ وَمَا قَلَىٰ'],
        '2:195' => ['en' => 'And spend in the way of Allāh and do not throw yourselves with your own hands into destruction. And do good; indeed, Allāh loves the doers of good.', 'ar' => 'وَأَنفِقُوا۟ فِى سَبِيلِ ٱللَّهِ وَلَا تُلْقُوا۟ بِأَيْدِيكُمْ إِلَى ٱلتَّهْلُكَةِ ۛ وَأَحْسِنُوٓا۟ ۛ إِنَّ ٱللَّهَ يُحِبُّ ٱلْمُحْسِنِينَ'],
        '4:29' => ['en' => 'O you who have believed, do not consume one another\'s wealth unjustly but only in lawful business by mutual consent. And do not kill yourselves. Indeed, Allāh is to you ever Merciful.', 'ar' => 'يَـٰٓأَيُّهَا ٱلَّذِينَ ءَامَنُوا۟ لَا تَأْكُلُوٓا۟ أَمْوَٰلَكُم بَيْنَكُم بِٱلْبَـٰطِلِ إِلَّآ أَن تَكُونَ تِجَـٰرَةً عَن تَرَاضٍ مِّنكُمْ ۚ وَلَا تَقْتُلُوٓا۟ أَنفُسَكُمْ ۚ إِنَّ ٱللَّهَ كَانَ بِكُمْ رَحِيمًا'],
        '4:30' => ['en' => 'And whoever does that in aggression and injustice - then We will drive him into a Fire. And that, for Allāh, is always easy.', 'ar' => 'وَمَن يَفْعَلْ ذَٰلِكَ عُدْوَٰنًا وَظُلْمًا فَسَوْفَ نُصْلِيهِ نَارًا ۚ وَكَانَ ذَٰلِكَ عَلَى ٱللَّهِ يَسِيرًا'],
    ];

    private const SUPPORT_RESOURCES = [
        'arabic' => 'إذا كنت تفكر في إيذاء نفسك، فأرجوك تواصل مع شخص تثق به أو مع خط المساعدة النفسية. أنت لست وحدك. الحياة ثمينة وهناك دائما أمل.',
        'english' => 'If you are thinking about harming yourself, please reach out to someone you trust or contact a crisis helpline. You are not alone. Life is precious and there is always hope.',
    ];

    public function handleInput(Request $request): JsonResponse
    {
        $request->validate([
            'input' => 'required|string|min:2|max:2000',
        ]);

        $userInput = $request->input('input');

        $classification = $this->classifyWithOpenAI($userInput);

        if (($classification['status'] ?? null) === 'rejected') {
            return response()->json([
                'status' => 'rejected',
                'message' => 'I cannot provide legal rulings, fatwas, medical advice, or halal/haram judgments. Please ask a question about your feelings or personal reflections.',
            ]);
        }

        $category = $classification['category'] ?? null;
        $language = $classification['language'] ?? 'english';
        $reflection = $classification['reflection'] ?? null;
        $isCrisis = $classification['crisis'] ?? false;
        $suggestedVerses = $classification['verses'] ?? [];

        if ($isCrisis) {
            $verses = $this->fetchIslamicAppVerses($suggestedVerses, $language)
                ?: $this->fetchFallbackVerses($this->crisisFallback, $language);

            return response()->json([
                'status' => 'approved',
                'category' => 'crisis',
                'language' => $language,
                'crisis' => true,
                'reflection' => $reflection,
                'verses' => $verses,
                'supportResources' => [
                    'message' => self::SUPPORT_RESOURCES[$language] ?? self::SUPPORT_RESOURCES['english'],
                ],
            ]);
        }

        $verses = $this->fetchIslamicAppVerses($suggestedVerses, $language);

        if (empty($verses) && $category && isset($this->fallbackVerseMap[$category])) {
            $verses = $this->fetchFallbackVerses($this->fallbackVerseMap[$category], $language);
        }

        if (empty($verses)) {
            $category = 'sadness';
            $language = 'english';
            $verses = $this->fetchFallbackVerses($this->fallbackVerseMap['sadness'], 'english');
        }

        return response()->json([
            'status' => 'approved',
            'category' => $category,
            'language' => $language,
            'reflection' => $reflection,
            'verses' => $verses,
        ]);
    }

    private function classifyWithOpenAI(string $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post(self::OPENAI_ENDPOINT, [
            'model' => 'gpt-4o',
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Return JSON: category, language, crisis (bool), reflection, verses (array of {surah, ayah}).

TOPIC CATEGORY — pick ONE:
- sadness: talking about depression, sadness, heartbreak, disappointment, hopelessness, crying, despair, feeling low
- anxiety: talking about fear, worry, stress, panic, being overwhelmed, nervous, scared, anxious about future
- loneliness: talking about being alone, isolated, no friends/family, abandoned, nobody cares, no support
- financial_worry: talking about money problems, debt, poverty, broke, need provision/rizq, bills, income, financial struggle
- yearning: talking about wanting marriage, a spouse, children, love, relationship, connection, longing to get married
- grief: talking about death of a loved one, loss, mourning, missing someone who died, bereavement
- guilt: talking about sin, regret, shame, remorse, repentance, asking forgiveness, "I did wrong"
- anger: talking about angry, furious, irritated, frustrated, resentful, rage
- happiness: talking about happy, joyful, excited, delighted, celebrating
- gratitude: talking about thankful, grateful, blessed, appreciative, shukr, alhamdulillah
- hope: talking about optimistic, hoping for good, trusting Allah, looking forward to positive change
- contentment: talking about peace, calm, satisfaction, tranquility, at peace, qalbi mutma\'inn
- rejected: ONLY for asking halal/haram rules, fatwa, Islamic legal rulings, medical diagnosis, legal advice

STRICT VERSE RELEVANCE RULES:
Every verse you suggest MUST be DIRECTLY about the user\'s specific topic. Look at what the user actually said and pick verses whose central theme matches.

KNOW WHAT EACH VERSE ACTUALLY SAYS. Do not guess. Only use verses you are certain about.
- 30:21 = "He created for you mates from among yourselves that you may find tranquility in them; He placed love and mercy between you" → DIRECTLY about marriage
- 25:74 = "Our Lord, grant us from among our spouses and offspring comfort to our eyes" → DIRECTLY about marriage/children
- 24:32 = "Marry the unmarried among you" → DIRECTLY about marriage
- 16:72 = "Allah made for you from yourselves mates and from your mates children" → DIRECTLY about marriage
- 2:187 = "They are clothing for you and you are clothing for them" → DIRECTLY about spouses
- 30:4 = "To Allah belongs the command before and after" → NOT about happiness, WRONG for happiness topic
- 20:20 = "He threw his staff and it became a snake" → NOT about marriage, DO NOT use for yearning
- 2:186 = "I am near, I answer the call" → about dua, NOT directly about marriage/loneliness
- 93:3 = "Your Lord has not forsaken you" → general comfort, NOT directly about marriage/sadness
- 28:24 = "My Lord, I am in need of whatever good You send" → general need, NOT directly about marriage

CORRECT EXAMPLES of topic → verses (ALL verses must be ON-TOPIC):
- "i need money" (financial_worry) → 65:2, 65:3, 11:6, 51:58, 2:261, 67:15 (all about provision/rizq)
- "أريد أن أتزوج" (yearning/marriage) → 30:21, 25:74, 24:32, 16:72, 2:187 (ALL directly about marriage/spouses)
- "im so depressed" (sadness) → 2:286, 39:53, 12:87, 94:5, 94:6, 3:139 (all about comfort/relief)
- "i want to kill myself" (crisis) → 2:195, 4:29, 4:30, 39:53, 12:87, 94:5 (quotes about NOT killing yourself + hope)
- "im happy" (happiness) → 10:58, 27:19, 30:4, 3:139, 15:98 (all about joy/celebration)
- "i feel lonely" (loneliness) → 2:186, 9:40, 50:16, 20:46, 28:7 (all about Allah being with you)
- "i miss my mom who died" (grief) → 2:156, 2:157, 16:127, 22:35 (all about patience in loss)
- "i sinned" (guilt) → 39:53, 42:25, 66:8, 25:70, 9:104 (all about forgiveness/repentance)
- "im scared" (anxiety) → 13:28, 65:2, 65:3, 29:69, 8:2 (all about finding peace in Allah)
- "thank you Allah" (gratitude) → 14:7, 55:13, 16:18, 2:152, 31:12 (all about thankfulness)
- "im angry" (anger) → 3:134, 42:37, 42:40, 25:63, 7:199 (all about controlling anger)

BAD EXAMPLES (WRONG — do not do this):
- For "أريد أن أتزوج", do NOT include 2:186 (Allah is near — NOT about marriage) or 93:3 (Allah hasn\'t forsaken you — NOT about marriage)
- For "أشعر باكتئاب شديد", do NOT include 2:186 or 93:3 unless the user is ALSO feeling abandoned by Allah

CORRECT BEHAVIOR: If user says "أريد أن أتزوج", ALL 5-6 verses must be about marriage/spouses/ nikah. NO general verses. Only 30:21, 25:74, 24:32, 16:72, 2:187, 42:11, 7:189, 4:1, 2:228, 4:19, etc.

LANGUAGE: "arabic" if input contains Arabic script, "english" otherwise.

CRISIS: true ONLY if user talks about suicide, self-harm, wanting to die, ending their life.

REFLECTION: Write 1 warm sentence about their specific topic in their language.

RULES:
1. Emotions and life struggles are ALWAYS approved.
2. loneliness = alone/isolated. Money = financial_worry. Marriage = yearning.
3. Every verse MUST be REAL (surah 1-114, ayah >= 1).
4. Default to "sadness" if unsure. NEVER default to rejected for emotions.',
                ],
                [
                    'role' => 'user',
                    'content' => $input,
                ],
            ],
        ]);

        if ($response->failed()) {
            return ['status' => 'rejected', 'category' => null, 'crisis' => false, 'verses' => []];
        }

        $body = $response->json();
        $content = json_decode($body['choices'][0]['message']['content'] ?? '{}', true);

        $category = $content['category'] ?? null;
        $status = $content['status'] ?? 'approved';

        if ($status === 'rejected' || $category === 'rejected') {
            return ['status' => 'rejected', 'category' => null, 'crisis' => false, 'verses' => []];
        }

        $verses = [];
        if (isset($content['verses']) && is_array($content['verses'])) {
            foreach ($content['verses'] as $v) {
                $surah = (int)($v['surah'] ?? 0);
                $ayah = (int)($v['ayah'] ?? 0);
                if ($surah >= 1 && $surah <= 114 && $ayah >= 1) {
                    $verses[] = ['surah' => $surah, 'ayah' => $ayah];
                }
            }
        }

        return [
            'status' => 'approved',
            'category' => $category,
            'language' => $content['language'] ?? 'english',
            'reflection' => $content['reflection'] ?? null,
            'crisis' => $content['crisis'] ?? false,
            'verses' => $verses,
        ];
    }

    private function fetchIslamicAppVerses(array $references, string $language): array
    {
        if (empty($references)) {
            return [];
        }

        $translation = $language === 'arabic'
            ? 'ar-quran-uthmani'
            : 'en-sahih-international';

        $verses = [];
        foreach ($references as $ref) {
            $surah = $ref['surah'];
            $ayah = $ref['ayah'];
            $reference = "{$surah}:{$ayah}";

            $response = Http::get(sprintf(self::ISLAMIC_APP_VERSES, $surah, $ayah), [
                'fields' => 'text_uthmani',
                'translations' => $translation,
            ]);

            if (!$response->successful()) {
                continue;
            }

            $data = $response->json();
            $verse = $data['data']['verse'] ?? [];

            $text = '';
            if ($language === 'arabic') {
                $text = $verse['text_uthmani'] ?? '';
            } else {
                $text = $verse['translations'][0]['text'] ?? $verse['text_uthmani'] ?? '';
            }

            if (!empty($text)) {
                $verses[] = [
                    'reference' => $reference,
                    'text' => $text,
                    'surah' => $surah,
                    'ayah' => $ayah,
                ];
            }
        }

        return $verses;
    }

    private function fetchFallbackVerses(array $references, string $language): array
    {
        $hash = crc32(json_encode($references) . $language . date('Y-m-d'));
        $count = count($references);
        $numToSelect = min(3, $count);

        $selectedKeys = [];
        for ($i = 0; $i < $numToSelect; $i++) {
            $key = abs($hash + $i * 7) % $count;
            if (!in_array($key, $selectedKeys)) {
                $selectedKeys[] = $key;
            } else {
                for ($j = 0; $j < $count; $j++) {
                    if (!in_array($j, $selectedKeys)) {
                        $selectedKeys[] = $j;
                        break;
                    }
                }
            }
        }

        $verses = [];
        foreach ($selectedKeys as $key) {
            $ref = $references[$key];
            $keyStr = "{$ref['surah']}:{$ref['ayah']}";
            if (isset($this->localVerseTexts[$keyStr])) {
                $texts = $this->localVerseTexts[$keyStr];
                $text = $language === 'arabic' ? $texts['ar'] : $texts['en'];
                $verses[] = [
                    'reference' => $keyStr,
                    'text' => $text,
                    'surah' => $ref['surah'],
                    'ayah' => $ref['ayah'],
                ];
            }
        }

        return $verses;
    }
}
