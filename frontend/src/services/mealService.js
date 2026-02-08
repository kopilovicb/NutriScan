import api from './api';

export const addMeal = async (data) => {
  const response = await api.post('/meals/add', {
        naziv: data.naziv,
    barkod: data.barkod,
    kalorije: data.kalorije,           // ✅ DODAJ
    proteini: data.proteini,           // ✅ DODAJ
    masti: data.masti,                 // ✅ DODAJ
    ugljeni_hidrati: data.ugljeni_hidrati, // ✅ DODAJ
    kolicina_grama: data.kolicina_grama || 100, // ✅ Uzmi iz data
  });

  return response.data;
};
export const addManualMeal = async (data) => {
  const res = await api.post('/meals/add_manual', data);
  return res.data;
};