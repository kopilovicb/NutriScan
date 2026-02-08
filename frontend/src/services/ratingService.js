import api from './api';

export const rateProduct = async (product, ocena) => {
  const res = await api.post('/products/rate', {
    naziv: product.naziv,
    barkod: product.barkod,
    ocena,
  });
  return res.data;
};

export const getAverageRating = async (product) => {
  const res = await api.get('/products/rating', {
    params: {
      naziv: product.naziv,
      barkod: product.barkod,
    },
  });
  return res.data;
};
