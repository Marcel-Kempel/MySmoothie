UPDATE ingredients
SET image_url = CASE id
  WHEN 1 THEN 'assets/images/ingredients/banane.jpeg'
  WHEN 2 THEN 'assets/images/ingredients/erdbeere.jpeg'
  WHEN 3 THEN 'assets/images/ingredients/mango.jpeg'
  WHEN 4 THEN 'assets/images/ingredients/ananas.jpeg'
  WHEN 5 THEN 'assets/images/ingredients/blaubeere.jpeg'
  WHEN 6 THEN 'assets/images/ingredients/kiwi.jpeg'
  WHEN 7 THEN 'assets/images/ingredients/orange.jpeg'
  WHEN 8 THEN 'assets/images/ingredients/apfel.jpeg'
  WHEN 9 THEN 'assets/images/ingredients/himbeere.jpeg'
  WHEN 10 THEN 'assets/images/ingredients/spinat.jpeg'
  WHEN 11 THEN 'assets/images/ingredients/gruenkohl.jpeg'
  WHEN 12 THEN 'assets/images/ingredients/karotte.jpeg'
  WHEN 13 THEN 'assets/images/ingredients/rote-beete.jpeg'
  WHEN 14 THEN 'assets/images/ingredients/gurke.jpeg'
  WHEN 15 THEN 'assets/images/ingredients/sellerie.jpeg'
  WHEN 16 THEN 'assets/images/ingredients/ingwer.jpeg'
  WHEN 17 THEN 'assets/images/ingredients/whey-vanille.jpeg'
  WHEN 18 THEN 'assets/images/ingredients/whey-schoko.jpeg'
  WHEN 19 THEN 'assets/images/ingredients/veganes-protein.jpeg'
  WHEN 20 THEN 'assets/images/ingredients/skyr.jpeg'
  WHEN 21 THEN 'assets/images/ingredients/sojaprotein.jpeg'
  WHEN 22 THEN 'assets/images/ingredients/hanfprotein.jpeg'
  WHEN 23 THEN 'assets/images/ingredients/erbsenprotein.jpeg'
  WHEN 24 THEN 'assets/images/ingredients/reisprotein.jpeg'
  ELSE image_url
END
WHERE id BETWEEN 1 AND 24;
