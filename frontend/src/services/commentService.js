import api from './api';

export const addComment = async (product, tekst) => {
  const res = await api.post('/products/comment', {
    naziv: product.naziv,
    barkod: product.barkod,
    tekst,
  });
  return res.data;
};

export const getComments = async (product) => {
  const res = await api.get('/products/comments', {
    params: {
      naziv: product.naziv,
      barkod: product.barkod,
    },
  });
  return res.data;
};
