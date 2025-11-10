/**
 * Mapeo de COMPANIA_ID a archivos de configuración
 *
 * Este archivo mapea cada COMPANIA_ID del .env a su archivo de configuración correspondiente
 *
 * Uso:
 * 1. Cambia COMPANIA_ID en .env
 * 2. app.config.js cargará automáticamente la configuración correcta
 * 3. No necesitas ejecutar comandos adicionales
 */

module.exports = {
  // Taxi Apps
  '373': 'vt-taxi',
  '380': 'mototaxi-bolivia',
  '388': 'latom',
  '401': 'transtours',
  '404': 'turemolque',
  '449': 'argenvios',
  '451': 'pacenos-driver',
  '453': 'iq-taxi',
  '454': 'taxi-durango',
  '455': 'global-express',
  '457': 'satelitaxi',

  // Marketplace Apps
  '385': 'vt-marketplace',
  '387': 'agrocomercio',
  '405': 'farmalife',
  '408': 'kass',
  '409': 'vt-comida',
  '441': 'la-huerta',
  '445': 'super-altos',
  '446': 'juma',
  '447': 'cml-store',
  '456': 'laprida',
  '460': '99-place',
  '462': 'amigo-market',
  '464': 'bufalo-soup',
  '468': 'canjear',
  '477': 'yego',

  // Servicios Apps
  '406': 'desim-latam',
  '440': 'mascotas-care',
  '444': 'indproyect',
  '452': 'med-fyndh-conecth',
  '393': 'perfect-beauty',

  // Billetera Apps
  '376': 'virtual-pay',
  '463': 'abunda-pay',
  '465': 'pymego',
  '467': 'tippal',

  // Gestión Apps
  '381': 'vt-gestion',
  '382': 'deprosur',
  '383': 'vt-hotel',

  // Otros Apps
  '374': 'loteria',
  '377': 'traslados-go',
  '379': 'toma-pedidos',
  '386': 'mitv-paraguay',
  '389': 'vt-recoleccion',
  '390': 'cuydadorrr',
  '391': 'justice',
  '392': 'fortune',
  '394': 'vt-recaudacion',
  '395': 'juegana',
  '402': 'vt-fitness',
  '410': 'vt-lock',
  '458': 'corsepsa',
  '459': 'reina-roja',
  '466': 'vt-panico',
  '470': 'control-glucemia',
  '472': 'avantyra',
};
