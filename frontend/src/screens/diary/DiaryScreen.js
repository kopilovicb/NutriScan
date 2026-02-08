import {
  View,
  Text,
  FlatList,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useEffect, useState } from 'react';
import { getTodayDiary } from '../../services/diaryService';

export default function DiaryScreen() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await getTodayDiary();
setData(res);

      } catch (e) {
        console.log(e);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  if (loading) {
    return <ActivityIndicator size="large" />;
  }

  if (!data || data.success !== true) {
    return <Text>Greška pri učitavanju</Text>;
  }

  return (
  <View style={styles.container}>
    <Text style={styles.title}>Dnevnik ishrane</Text>
    <Text style={styles.subtitle}>Danas</Text>

    {/* TOTAL CARD */}
    <View style={styles.totalCard}>
      <Text style={styles.totalLabel}>Ukupno kalorija</Text>
      <Text style={styles.totalValue}>
        {Number(data.totalCalories || 0)} kcal
      </Text>
    </View>

    {data.items.length === 0 ? (
      <View style={styles.emptyBox}>
        <Text style={styles.emptyText}>
          Još nema unetih obroka za danas 🍽️
        </Text>
      </View>
    ) : (
      <FlatList
        data={data.items}
        keyExtractor={(_, i) => i.toString()}
        contentContainerStyle={{ paddingBottom: 40 }}
        renderItem={({ item }) => (
          <View style={styles.mealCard}>
            <View style={styles.mealHeader}>
              <Text style={styles.mealName}>
                {item.naziv}
              </Text>
              <Text style={styles.mealCalories}>
                {item.kalorije} kcal
              </Text>
            </View>

            <Text style={styles.mealGrams}>
              {item.kolicina_grama} g
            </Text>
          </View>
        )}
      />
    )}
  </View>
);

}
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
    padding: 20,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
  },
  subtitle: {
    color: '#8E8E93',
    marginBottom: 20,
  },

  /* TOTAL CARD */
  totalCard: {
    backgroundColor: '#5B5BEA',
    borderRadius: 20,
    padding: 20,
    marginBottom: 25,
  },
  totalLabel: {
    color: '#E0E0FF',
    fontSize: 14,
  },
  totalValue: {
    color: '#fff',
    fontSize: 28,
    fontWeight: '800',
    marginTop: 5,
  },

  /* MEAL CARD */
  mealCard: {
    backgroundColor: '#F7F8FC',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
  },
  mealHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  mealName: {
    fontWeight: '700',
    fontSize: 16,
    flex: 1,
    marginRight: 10,
  },
  mealCalories: {
    fontWeight: '700',
    color: '#5B5BEA',
  },
  mealGrams: {
    marginTop: 4,
    color: '#8E8E93',
  },

  /* EMPTY */
  emptyBox: {
    marginTop: 40,
    alignItems: 'center',
  },
  emptyText: {
    color: '#8E8E93',
    fontSize: 16,
  },
});
