import api from './api';

export const getTodayDiary = async () => {
  const response = await api.get('/meals/today');
  return response.data;
};
