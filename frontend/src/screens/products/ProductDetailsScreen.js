import {
  View,
  Text,
  ActivityIndicator,
  Alert,
  TextInput,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';

import { useEffect, useState } from 'react';
import { useLocalSearchParams } from 'expo-router';

import { getProductByBarcode } from '../../services/productService';
import { addMeal } from '../../services/mealService';
import { addComment, getComments } from '../../services/commentService';
import { rateProduct, getAverageRating } from '../../services/ratingService';

export default function ProductDetailsScreen() {
  const { barcode } = useLocalSearchParams();

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);

  const [comments, setComments] = useState([]);
  const [avgRating, setAvgRating] = useState(0);

  const [tekst, setTekst] = useState('');
  const [ocena, setOcena] = useState('');

  // =========================
  // LOAD PRODUCT
  // =========================
  useEffect(() => {
    const loadProduct = async () => {
      try {
        const data = await getProductByBarcode(barcode);
        setProduct(data);
      } catch (err) {
        console.log(err);
      } finally {
        setLoading(false);
      }
    };
    loadProduct();
  }, [barcode]);

  // 🔑 JEDINSTVEN, SIGURAN NAZIV
  const safeNaziv =
    product?.naziv && product.naziv.trim() !== ''
      ? product.naziv
      : product?.brend && product.brend.trim() !== ''
      ? product.brend
      : `Proizvod ${barcode}`;

  // =========================
  // LOAD COMMENTS + RATING
  // =========================
  useEffect(() => {
    if (!product) return;

    const loadExtras = async () => {
      try {
        const c = await getComments({ naziv: safeNaziv });
        setComments(Array.isArray(c?.comments) ? c.comments : []);

        const r = await getAverageRating({ naziv: safeNaziv });
        setAvgRating(r?.avg || 0);
      } catch (e) {
        console.log(e);
        setComments([]);
        setAvgRating(0);
      }
    };

    loadExtras();
  }, [product]);

  if (loading) return <ActivityIndicator size="large" />;
  if (!product) return <Text>Proizvod nije pronađen</Text>;

  // =========================
  // ADD TO MEAL
  // =========================
const handleAddMeal = async () => {
  try {
    const res = await addMeal({
      naziv: safeNaziv,
      barkod: barcode,
      kalorije: product.kalorije,
      proteini: product.proteini,
      masti: product.masti,
      ugljeni_hidrati: product.ugljeni_hidrati,  
          kolicina_grama: 100,  

    });

    console.log('ADD MEAL RESPONSE:', res);

    if (res.success) {
      Alert.alert('Uspeh', 'Proizvod dodat u dnevnik ishrane');
    } else {
      Alert.alert('Greška', JSON.stringify(res));
    }
  } catch (err) {
    console.log('ADD MEAL ERROR:', err?.response?.data);
    console.log('STATUS:', err?.response?.status);

    Alert.alert(
      'Greška',
      JSON.stringify(err?.response?.data || 'Nepoznata greška')
    );
  }
};


  // =========================
  // ADD COMMENT + RATE
  // =========================
  const handleSubmit = async () => {
    if (!ocena || ocena < 1 || ocena > 5) {
      Alert.alert('Greška', 'Ocena mora biti između 1 i 5');
      return;
    }

    if (!tekst.trim()) {
      Alert.alert('Greška', 'Komentar ne može biti prazan');
      return;
    }

    try {
      await rateProduct({ naziv: safeNaziv }, Number(ocena));
      await addComment({ naziv: safeNaziv }, tekst);

      setOcena('');
      setTekst('');

      const c = await getComments({ naziv: safeNaziv });
      setComments(Array.isArray(c?.comments) ? c.comments : []);

      const r = await getAverageRating({ naziv: safeNaziv });
      setAvgRating(r?.avg || 0);
    } catch (e) {
      console.log('COMMENT ERROR RESPONSE:', e?.response?.data);
      console.log('STATUS:', e?.response?.status);
      Alert.alert('Greška', JSON.stringify(e?.response?.data));
    }
  };

  return (
  <View style={styles.container}>
    <View style={styles.sheet}>
      <Text style={styles.brand}>
        {product.brend || 'PROIZVOD'}
      </Text>

      <Text style={styles.title}>{safeNaziv}</Text>

      {/* MACROS */}
      <View style={styles.macros}>
        <Macro label="🔥 kcal" value={product.kalorije} />
        <Macro label="⚡ protein" value={product.proteini} />
        <Macro label="💧 fats" value={product.masti} />
      </View>

      <TouchableOpacity
        style={styles.addBtn}
        onPress={handleAddMeal}
      >
        <Text style={styles.addText}>＋ Dodaj u dnevnik</Text>
      </TouchableOpacity>

      <Text style={styles.avg}>
        ⭐ Prosečna ocena: {avgRating}
      </Text>

      {/* COMMENT */}
      <Text style={styles.section}>Ostavi komentar</Text>

      <TextInput
        placeholder="Ocena (1–5)"
        keyboardType="numeric"
        value={ocena}
        onChangeText={setOcena}
        style={styles.input}
      />

      <TextInput
        placeholder="Komentar"
        value={tekst}
        onChangeText={setTekst}
        style={[styles.input, { height: 80 }]}
        multiline
      />

      <TouchableOpacity
        style={styles.sendBtn}
        onPress={handleSubmit}
      >
        <Text style={styles.sendText}>Pošalji</Text>
      </TouchableOpacity>

      {/* COMMENTS */}
      <Text style={styles.section}>Komentari</Text>

      {comments.length === 0 ? (
        <Text style={styles.empty}>Nema komentara</Text>
      ) : (
        comments.map((c, i) => (
          <View key={i} style={styles.comment}>
            <Text style={styles.commentUser}>
              {c.ime || 'Korisnik'}
            </Text>
            <Text>{c.tekst}</Text>
          </View>
        ))
      )}
    </View>
  </View>
);
}
const Macro = ({ label, value }) => (
  <View style={styles.macro}>
    <Text style={styles.macroValue}>{value}</Text>
    <Text style={styles.macroLabel}>{label}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: '#fff',
    padding: 20,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    minHeight: '85%',
  },
  brand: {
    color: '#8E8E93',
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    marginBottom: 20,
  },
  macros: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  macro: {
    alignItems: 'center',
    width: '30%',
  },
  macroValue: {
    fontSize: 22,
    fontWeight: '700',
  },
  macroLabel: {
    color: '#8E8E93',
  },
  addBtn: {
    backgroundColor: '#5B5BEA',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginBottom: 10,
  },
  addText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 16,
  },
  avg: {
    marginVertical: 10,
    fontWeight: '600',
  },
  section: {
    fontSize: 18,
    fontWeight: '700',
    marginVertical: 10,
  },
  input: {
    backgroundColor: '#F1F2F6',
    borderRadius: 14,
    padding: 12,
    marginBottom: 10,
  },
  sendBtn: {
    backgroundColor: '#1E1E2D',
    padding: 14,
    borderRadius: 14,
    alignItems: 'center',
    marginBottom: 20,
  },
  sendText: {
    color: '#fff',
    fontWeight: '600',
  },
  comment: {
    backgroundColor: '#F7F8FC',
    padding: 12,
    borderRadius: 12,
    marginBottom: 8,
  },
  commentUser: {
    fontWeight: '600',
    marginBottom: 2,
  },
  empty: {
    color: '#8E8E93',
  },
});
