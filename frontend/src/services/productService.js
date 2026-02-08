import api from './api';

export const getProductByBarcode = async (barcode) => {
  const response = await api.get('/products/barcode', {
    params: { barcode },
  });

  // DEBUG (privremeno)
  console.log('API RESPONSE:', response.data);

  if (response.data && response.data.success === true) {
    return response.data.product; // 👈 MORA OVO
  }

  return null;
};
