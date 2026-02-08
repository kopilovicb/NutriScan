import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Alert,
  StyleSheet,
} from 'react-native';
import { useState } from 'react';
import { addManualMeal } from '../../services/mealService';
import { useRouter } from 'expo-router';

export default function AddManualMealScreen() {
  const router = useRouter();

  const [naziv, setNaziv] = useState('');
  const [kalorije, setKalorije] = useState('');
  const [proteini, setProteini] = useState('');
  const [masti, setMasti] = useState('');
  const [uh, setUh] = useState('');
  const [kolicina, setKolicina] = useState('100');

  const handleSubmit = async () => {
    if (!naziv.trim()) {
      Alert.alert('Greška', 'Naziv je obavezan');
      return;
    }

    try {
      await addManualMeal({
        naziv,
        kalorije,
        proteini,
        masti,
        ugljeni_hidrati: uh,
        kolicina_grama: kolicina,
      });

      Alert.alert('Uspeh', 'Proizvod dodat u dnevnik', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    } catch (e) {
      Alert.alert('Greška', 'Neuspešno dodavanje');
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Dodaj proizvod</Text>

      {/* ⬇️ NAZIV – SLOVA */}
      <TextInputText
        label="Naziv proizvoda"
        value={naziv}
        set={setNaziv}
      />

      {/* ⬇️ SVE ISPOD – BROJEVI */}
      <TextInputNumber label="Kalorije" value={kalorije} set={setKalorije} />
      <TextInputNumber label="Proteini (g)" value={proteini} set={setProteini} />
      <TextInputNumber label="Masti (g)" value={masti} set={setMasti} />
      <TextInputNumber label="UH (g)" value={uh} set={setUh} />
      <TextInputNumber
        label="Količina (g)"
        value={kolicina}
        set={setKolicina}
      />

      <TouchableOpacity style={styles.btn} onPress={handleSubmit}>
        <Text style={styles.btnText}>➕ Dodaj u dnevnik</Text>
      </TouchableOpacity>
    </View>
  );
}

/* ===================== */
/* INPUT KOMPONENTE */
/* ===================== */

// ✅ TEXT INPUT (SLOVA)
const TextInputText = ({ label, value, set }) => (
  <TextInput
    placeholder={label}
    value={value}
    onChangeText={set}
    autoCapitalize="words"
    keyboardType="default"
    style={styles.input}
  />
);

// ✅ NUMBER INPUT (BROJEVI)
const TextInputNumber = ({ label, value, set }) => (
  <TextInput
    placeholder={label}
    value={value}
    onChangeText={set}
    keyboardType="numeric"
    style={styles.input}
  />
);

/* ===================== */
/* STYLES */
/* ===================== */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    marginBottom: 20,
  },
  input: {
    backgroundColor: '#F1F2F6',
    borderRadius: 14,
    padding: 12,
    marginBottom: 10,
  },
  btn: {
    backgroundColor: '#5B5BEA',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginTop: 10,
  },
  btnText: {
    color: '#fff',
    fontWeight: '700',
  },
});
