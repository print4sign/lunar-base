<div class="space-y-8">
    {{-- Page Title --}}
    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
        Specificaties
    </h2>

    {{-- Product Specifications Section --}}
    <div class="space-y-4">
        <h3 class="text-base font-medium text-gray-500 dark:text-gray-400">
            Product
        </h3>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left">
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                            Formaten
                        </td>
                        <td class="w-2/3 px-4 py-3 text-gray-700 dark:text-gray-300">
                            Straightflag: 40 x 235 cm, 80 x 220 cm, 65 x 315 cm, 80 x 315 cm en 90 x 430 cm<br>
                            Squareflag: 75 x 180 cm, 75 x 267 cm en 75 x 375,5 cm<br>
                            Dropflag: 70 x 180 cm en 100 x 250 cm<br>
                            Waveflag: 70 x 210 cm, 76 x 315 cm en 80 x 422 cm
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <td class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                            Accessoires
                        </td>
                        <td class="w-2/3 px-4 py-3 text-gray-700 dark:text-gray-300">
                            Pole inclusief tas, grondplug, grondpen, standaard, waterzak, parasolvoet, rotator en voetplaat
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                            Translucent
                        </td>
                        <td class="w-2/3 px-4 py-3 text-gray-700 dark:text-gray-300">
                            Flag longlife: voor 90 tot 95%<br>
                            Flag en Flag PET: voor 98%
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <td class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                            Levensduur
                        </td>
                        <td class="w-2/3 px-4 py-3 text-gray-700 dark:text-gray-300">
                            Flag longlife: drie tot vijf maanden, tot windkracht zes<br>
                            Flag en Flag PET: drie tot vier maanden, tot windkracht zes
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                            Extra
                        </td>
                        <td class="w-2/3 px-4 py-3 text-gray-700 dark:text-gray-300">
                            Bestel je een groot formaat Beachflag in combinatie met een parasolvoet, dan is een rotator noodzakelijk.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Material Comparison Section --}}
    <div class="space-y-4" x-data="{ selectedMaterials: ['flag', 'flag-longlife', 'flag-pet'] }">
        <h3 class="text-base font-medium text-gray-500 dark:text-gray-400">
            Materiaal
        </h3>

        {{-- Scroll container with indicators --}}
        <div class="relative">
            {{-- Horizontal scroll hint indicator --}}
            <div class="flex items-center justify-end gap-1 text-xs text-gray-400 mb-2">
                <span>Scroll voor meer</span>
                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </div>

            <div class="min-w-0 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                {{-- Right fade gradient to indicate more content --}}
                <div class="pointer-events-none absolute right-0 top-8 bottom-0 w-8 bg-gradient-to-l from-white dark:from-gray-900 to-transparent z-10"></div>

                <table class="min-w-max w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead>
                        <tr>
                            <th class="sticky left-0 z-20 px-4 py-3 bg-white dark:bg-gray-900 font-medium text-gray-900 dark:text-white min-w-[180px]">
                                Eigenschap
                            </th>
                            <th class="px-4 py-3 bg-white dark:bg-gray-900 font-medium text-gray-900 dark:text-white border-l border-gray-200 dark:border-gray-700 min-w-[160px]">
                                Flag
                            </th>
                            <th class="px-4 py-3 bg-white dark:bg-gray-900 font-medium text-gray-900 dark:text-white border-l border-gray-200 dark:border-gray-700 min-w-[160px]">
                                Flag Longlife
                            </th>
                            <th class="px-4 py-3 bg-white dark:bg-gray-900 font-medium text-gray-900 dark:text-white border-l border-gray-200 dark:border-gray-700 min-w-[200px]">
                                Flag PET
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- Row 1: Maximale printbreedte --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Maximale printbreedte (cm)
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">310</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">303</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">310</td>
                        </tr>
                        {{-- Row 2: Gewicht --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Gewicht in g/m2
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">110</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">115</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">110</td>
                        </tr>
                        {{-- Row 3: Printtechniek --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Printtechniek
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Sublimatie</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Sublimatie</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Sublimatie</td>
                        </tr>
                        {{-- Row 4: Basismateriaal --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Basismateriaal
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Polyester</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Polyester</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Biologisch afbreekbaar polyester</td>
                        </tr>
                        {{-- Row 5: Binnen / Buiten --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Binnen / Buiten
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Binnen, Buiten</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Binnen, Buiten</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Binnen, Buiten</td>
                        </tr>
                        {{-- Row 6: PVC-vrij --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                PVC-vrij
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                        </tr>
                        {{-- Row 7: Levertijd --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Levertijd (in dagen)
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">2</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">1</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">4</td>
                        </tr>
                        {{-- Row 8: Prijscategorie --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Prijscategorie
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">€</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">€</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">€</td>
                        </tr>
                        {{-- Row 9: Maximale printhoogte --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Maximale printhoogte (cm)
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">2400</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">2400</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">2400</td>
                        </tr>
                        {{-- Row 10: Afwerking --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Afwerking
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Snijden, Zomen, Band en ringen, Band en D-ringen, Band en koord, Klittenband, Band en haken, Band, koord en lus, Tunnel (dichtgestikt), Tunnel</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Snijden, Band en ringen, Band en koord, Klittenband, Band en haken, Band, koord en lus, Tunnel (dichtgestikt), Tunnel</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Snijden, Zomen, Band en D-ringen, Band en ringen, Band en koord, Klittenband, Band en haken, Band, koord en lus, Tunnel, Tunnel (dichtgestikt)</td>
                        </tr>
                        {{-- Row 11: Levensduur --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Levensduur
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Kort (minder dan 2 jaar)</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Kort (minder dan 2 jaar)</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Kort (minder dan 2 jaar)</td>
                        </tr>
                        {{-- Row 12: Bedrukking --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Bedrukking
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">100% Full color</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">100% Full color</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">100% Full color</td>
                        </tr>
                        {{-- Row 13: Brandcertificaat --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Brandcertificaat
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 14: Brandklasse --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Brandklasse
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">EN-13501: B-s1, d0</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">EN-13501: B-s1, d0</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">EN-13501: B-s1, d0</td>
                        </tr>
                        {{-- Row 15: Eigen vorm snijden --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Eigen vorm snijden
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 16: Extra groot formaat --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Extra groot formaat
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                        </tr>
                        {{-- Row 17: Doordruk --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Doordruk
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 18: Geluiddoorlatend --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Geluiddoorlatend
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 19: Winddoorlatend --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Winddoorlatend
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Ja</td>
                        </tr>
                        {{-- Row 20: Wasbaar --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Wasbaar
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 21: Land van herkomst --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Land van herkomst
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Duitsland</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Duitsland</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Duitsland</td>
                        </tr>
                        {{-- Row 22: Minimale afname --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Minimale afname
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">0.75</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">0.75</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">0.75</td>
                        </tr>
                        {{-- Row 23: Waterafstotend --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Waterafstotend
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 24: Gerecycled materiaal --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Gerecycled materiaal
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 25: HS-code --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                HS-code
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">60053600</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">60053600</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                        </tr>
                        {{-- Row 26: Materiaal categorie --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Materiaal categorie
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Vlag</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Vlag</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Vlag</td>
                        </tr>
                        {{-- Row 27: Blockout --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Blockout
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 28: Dubbelzijdig --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Dubbelzijdig
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        {{-- Row 29: Duurzamer --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Duurzamer
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Energiebesparend, Herkomst, Inkt op waterbasis, PVC-vrij, Recyclebaar</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Energiebesparend, Herkomst, Inkt op waterbasis, PVC-vrij, Recyclebaar</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Energiebesparend, Herkomst, Inkt op waterbasis, PVC-vrij, Recyclebaar</td>
                        </tr>
                        {{-- Row 30: Extra --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Extra
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Flag Longlife word opgevouwen verzonden.</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">Flag PET wordt opgevouwen verzonden.</td>
                        </tr>
                        {{-- Row 31: Backlit --}}
                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                Backlit
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                        </tr>
                        {{-- Row 32: Akoestisch --}}
                        <tr>
                            <td class="sticky left-0 z-10 px-4 py-3 font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-900">
                                Akoestisch
                            </td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                            <td class="px-4 py-3 border-l border-gray-200 dark:border-gray-700">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
