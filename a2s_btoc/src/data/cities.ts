export const moroccanCities = [
  'Agadir', 'Aïn Harrouda', 'Al Aaroui', 'Al Hoceima', 
  'Assilah', 'Azemmour', 'Azilal', 'Azrou', 'Bab Berred', 'Ben Ahmed', 'Ben Guerir',
  'Beni Mellal', 'Ben Yakhlef', 'Benslimane', 'Berkane', 'Berrechid', 'Bir Jdid',
  'Boujdour', 'Bouguedra', 'Bouskoura', 'Bouznika', 'Casablanca', 'Chefchaouen', 
  'Chtouka', 'Dakhla', 'Dar Bouazza', 'Edaoura', 'El Gara', 'El Jadida',
  'Er-Rich', 'Errachidia', 'Essaouira', 'Fès', 'Fkih Ben Saleh', 'Fnideq', 
  'Guelmim', 'Guercif', 'Had Soualem', 'Ifrane', 'Inzegane', 'Jerada', 
  'Kasba Tadla', 'Kelaa Sraghna', 'Kénitra', 'Khemis Des Znaga', 'Khemisset', 
  'Khouribga', 'Ksar El Kebir', 'Ksar Es Seghir', 'Laâyoune', 'Larache', 
  'Lissasfa', 'Marrakech', 'Martil', 'Médiouna', 'Meknès', 'Merzouga', 
  'Mghila', 'Midelt', 'Mohammedia', 'Moulay Bouazza', 'Nador', 'Nouaceur', 
  'Ouarzazate', 'Oued Zem', 'Oujda', 'Oulad Berh', 'Oulad Teima', 'Rabat', 
  'Safi', 'Sala Al Jadida', 'Salé', 'Sefrou', 'Settat', 'Sidi Allal Tazi', 
  'Sidi Bennour', 'Sidi Bou Othmane', 'Sidi Hajjaj Oued Hassar', 'Sidi Ifni', 
  'Sidi Kacem', 'Sidi Rahal', 'Sidi Slimane', 'Sidi Smail', 'Sidi Yahya', 
  'Skhirat', 'Taghazout', 'Tamaris', 'Tamesna', 'Tanger', 'Tan-Tan', 
  'Taourirt', 'Taroudant', 'Tata', 'Taza', 'Tazarine', 'Témara', 'Tétouan', 
  'Tiflet', 'Tinghir', 'Tit Mellil', 'Tiznit', 'Zagora', 'Zenata' ,'Tahla', 'Khenifra', 'Figuigue', 'Tanguist', 'Martil', 'Zawyat cheikh','agourai', 'ahfir', 'ain aouda', 'ain atig', 'ain chqef', 'ain taoujdate', 'ain zarka', 'ait kamra',
'ait melloul', 'ait moussa', 'ajdir', 'aklim', 'amsa', 'arbaat aounat', 'arfoud',
'arouit', 'azla', 'azrou- temsia', 'bab taza', 'bassatine elmenzah', 'belaaguid', 'beni hlala',
'benkarich', 'bentaib', 'bhalil', 'bni bouaayech', 'bni boufrah', 'bni chiker', 'bni hassan',
'bni hdifa', 'bnidrar', 'bninsar', 'bouderbala', 'boudnib', 'boufakrane', 'boujniba', 'boukidan',
'boulanoir', 'boulmane', 'boumia', 'bouzaghlal', 'bouzoug', 'cabo negro', 'dar akobaa',
'dardara', 'dcheira', 'deroua', 'drarga', 'driouch', 'echemmaia', 'el hadj kaddour', 'el hajeb',
'elaioune', 'farkhana', 'goulmima', 'guigou', 'gzenaya', 'had hrara',
'haouzia', 'harhoura', 'hattane', 'imouzzer kandar', 'imzourren', 'issaguen', 'jamaat saddina',
'jemaa shaim', 'kariat arkman', 'kenitra', 'khemis des zemamra', 'laatamna', 'laayoune',
'madagh', 'marina smir', 'mdiq', 'mediouna', 'mejjat', 'meknes', 'mers elkhir', 'mhaya', 'midar',
'missour', 'mohammadia', 'moulay abdellah', 'moulay idriss', 'moulay yacoub', 'mrirt', 'naima',
'ouaht sidi brahim', 'oualidia', 'oued amlil', 'oued cherrat', 'oued jdida', 'oued lew', 'ouislane',
'oulad amrane', 'oulad azouz', 'oulad ettayeb', 'oulad sidi ali ben youssef', 'ouled frej',
'outat lhaj', 'ras el ain - youssofia', 'rissani', 'sabaa aiyoun', 'saidia', 'sale', 'sebt gzoula',
'selouane', 'sidi ahmed', 'sidi boumehdi', 'sidi bouzid', 'sidi hajjaj', 'sidi harazem',
'sidi yahya oujda', 'sidi yahya zear', 'targuist', 'tassoultante', 'temara', 'tetouan',
'tikiouine', 'tinjdad', 'tiztoutine', 'tlat bougedra', 'tnine chtouka', 'toulal', 'youssofia',
'zaida', 'zaio', 'zeghanghane','Autre'

].sort();

export const getShippingCost = (city: string): number => {
  // Frais de livraison spéciaux pour certaines villes (20 DH)
  const specialCities = ['Casablanca', 'Mohammedia', 'Témara', 'Rabat', 'Salé'];
  return specialCities.includes(city) ? 20 : 35;
};