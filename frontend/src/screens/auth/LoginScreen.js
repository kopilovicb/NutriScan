import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  StyleSheet,
} from 'react-native';
import { useState } from 'react';
import { useRouter } from 'expo-router';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [lozinka, setLozinka] = useState('');
  const [loading, setLoading] = useState(false);

  const router = useRouter();

  const handleLogin = async () => {
    if (!email || !lozinka) {
      Alert.alert('Greška', 'Unesi email i lozinku');
      return;
    }

    try {
      setLoading(true);

      const res = await api.post('/login', {
        email,
        lozinka,
      });

      if (res.data.success) {
        await AsyncStorage.setItem('token', res.data.token);
        await AsyncStorage.setItem('ime', res.data.user.ime);
        await AsyncStorage.setItem('email', res.data.user.email);

        router.replace('/dashboard');
      } else {
        Alert.alert('Greška', res.data.error || 'Login neuspešan');
      }
    } catch (e) {
      Alert.alert('Greška', 'Greška pri konekciji sa serverom');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      {/* HEADER */}
      <View style={styles.header}>
        <Text style={styles.logo}>NutriScan</Text>
        <Text style={styles.subtitle}>
          Prati ishranu. Skeniraj pametno.
        </Text>
      </View>

      {/* CARD */}
      <View style={styles.card}>
        <Text style={styles.title}>Prijava</Text>

        <TextInput
          placeholder="Email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
          style={styles.input}
        />

        <TextInput
          placeholder="Lozinka"
          value={lozinka}
          onChangeText={setLozinka}
          secureTextEntry
          style={styles.input}
        />

        <TouchableOpacity
          style={styles.primaryBtn}
          onPress={handleLogin}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.primaryText}>Prijavi se</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.secondaryBtn}
          onPress={() => router.push('/register')}
        >
          <Text style={styles.secondaryText}>
            Nemaš nalog? Registruj se
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

/* ===================== */
/* STYLES */
/* ===================== */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#5B5BEA',
    justifyContent: 'center',
    padding: 20,
  },
  header: {
    marginBottom: 30,
  },
  logo: {
    fontSize: 34,
    fontWeight: '800',
    color: '#fff',
  },
  subtitle: {
    color: '#E0E0FF',
    marginTop: 5,
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    marginBottom: 20,
  },
  input: {
    backgroundColor: '#F1F2F6',
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
  },
  primaryBtn: {
    backgroundColor: '#5B5BEA',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginTop: 10,
  },
  primaryText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 16,
  },
  secondaryBtn: {
    marginTop: 15,
    alignItems: 'center',
  },
  secondaryText: {
    color: '#5B5BEA',
    fontWeight: '600',
  },
});
