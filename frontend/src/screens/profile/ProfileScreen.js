import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useRouter } from 'expo-router';
import { useEffect, useState } from 'react';

export default function ProfileScreen() {
  const router = useRouter();

  const [user, setUser] = useState({
    ime: '',
    email: '',
  });

  useEffect(() => {
    const loadUser = async () => {
      const ime = await AsyncStorage.getItem('ime');
      const email = await AsyncStorage.getItem('email');

      setUser({
        ime: ime || 'Guest User',
        email: email || 'No email provided',
      });
    };

    loadUser();
  }, []);

  const handleLogout = async () => {
    Alert.alert(
      'Logout',
      'Da li si siguran da želiš da se odjaviš?',
      [
        { text: 'Otkaži', style: 'cancel' },
        {
          text: 'Odjavi se',
          style: 'destructive',
          onPress: async () => {
            await AsyncStorage.clear();
            router.replace('/');
          },
        },
      ]
    );
  };

  const initial = user.ime?.charAt(0)?.toUpperCase() || '?';

  return (
    <View style={styles.container}>
      {/* AVATAR */}
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>{initial}</Text>
      </View>

      <Text style={styles.name}>{user.ime}</Text>
      <Text style={styles.email}>{user.email}</Text>

      {/* ACCOUNT DETAILS */}
      <View style={styles.section}>
        <ProfileItem label="Name" value={user.ime} />
        <ProfileItem label="Email" value={user.email} />
      </View>

      {/* LOGOUT */}
      <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
        <Text style={styles.logoutText}>Sign Out</Text>
      </TouchableOpacity>
    </View>
  );
}

/* ===================== */
/* COMPONENT */
/* ===================== */
const ProfileItem = ({ label, value }) => (
  <View style={styles.item}>
    <Text style={styles.itemLabel}>{label}</Text>
    <Text style={styles.itemValue}>{value}</Text>
  </View>
);

/* ===================== */
/* STYLES */
/* ===================== */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
    alignItems: 'center',
    padding: 20,
  },
  avatar: {
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: '#5B5BEA',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 20,
  },
  avatarText: {
    color: '#fff',
    fontSize: 36,
    fontWeight: '800',
  },
  name: {
    fontSize: 22,
    fontWeight: '700',
    marginTop: 15,
  },
  email: {
    color: '#8E8E93',
    marginBottom: 30,
  },
  section: {
    width: '100%',
    backgroundColor: '#F7F8FC',
    borderRadius: 16,
    padding: 16,
    marginBottom: 30,
  },
  item: {
    marginBottom: 12,
  },
  itemLabel: {
    color: '#8E8E93',
    fontSize: 12,
  },
  itemValue: {
    fontSize: 16,
    fontWeight: '600',
  },
  logoutBtn: {
    borderWidth: 1,
    borderColor: '#FF3B30',
    paddingVertical: 14,
    paddingHorizontal: 40,
    borderRadius: 14,
  },
  logoutText: {
    color: '#FF3B30',
    fontWeight: '700',
  },
});
