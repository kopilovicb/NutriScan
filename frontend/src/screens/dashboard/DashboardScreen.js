import {
  View,
  Text,
  ActivityIndicator,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import { useState, useCallback } from 'react';
import { useRouter, useFocusEffect } from 'expo-router';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

// 🎯 FIKSNI DNEVNI CILJ
const DAILY_GOAL = 2200;

export default function DashboardScreen() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  const loadDashboard = async () => {
    try {
      setLoading(true);
      const res = await api.get('/dashboard');
      if (res.data.success) {
        setData(res.data);
      }
    } catch (e) {
      console.log(e);
    } finally {
      setLoading(false);
    }
  };

  // 🔄 refresh svaki put kad se vratiš na dashboard
  useFocusEffect(
    useCallback(() => {
      loadDashboard();
    }, [])
  );

  const handleLogout = async () => {
    await AsyncStorage.removeItem('token');
    router.replace('/');
  };

  if (loading) return <ActivityIndicator size="large" />;
  if (!data) return <Text>Greška pri učitavanju</Text>;

  return (
    <View style={styles.container}>
      {/* HEADER */}
      <Text style={styles.hello}>Zdravo, {data.name} 👋</Text>
      <Text style={styles.sub}>Evo pregleda za danas</Text>

      {/* STATS */}
      <View style={styles.row}>
        <StatCard
          title="Kalorije"
          value={`${data.caloriesConsumed} kcal`}
          icon="🔥"
        />
        <StatCard
          title="Unosi"
          value={data.scansToday}
          icon="🧾"
        />
      </View>

   

      {/* DAILY GOAL */}
      <View style={styles.goalBox}>
        <Text style={styles.goalTitle}>Dnevni cilj</Text>

        <ProgressBar
          value={data.caloriesConsumed / DAILY_GOAL}
        />

        <Text style={styles.goalText}>
          {data.caloriesConsumed} / {DAILY_GOAL} kcal
        </Text>

        <Text style={styles.streakHint}>
          {getStreakMessage(data.streak)}
        </Text>
      </View>

      {/* ACTIONS */}
      <TouchableOpacity
        style={styles.primaryBtn}
        onPress={() => router.push('/scan')}
      >
        <Text style={styles.primaryText}>📷 Skeniraj barkod</Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.primaryBtnAlt}
        onPress={() => router.push('/add-manual-meal')}
      >
        <Text style={styles.primaryTextAlt}>
          ➕ Dodaj proizvod ručno
        </Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.secondaryBtn}
        onPress={() => router.push('/diary')}
      >
        <Text style={styles.secondaryText}>📊 Dnevnik ishrane</Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.secondaryBtn}
        onPress={() => router.push('/profile')}
      >
        <Text style={styles.secondaryText}>👤 Profil</Text>
      </TouchableOpacity>

      <TouchableOpacity onPress={handleLogout} style={styles.logout}>
        <Text style={styles.logoutText}>Odjavi se</Text>
      </TouchableOpacity>
    </View>
  );
}

/* ===================== */
/* HELPERS */
/* ===================== */

function getStreakMessage(streak) {
  if (streak >= 7) return '🏆 Odlična navika!';
  if (streak >= 3) return '🔥 U formi si';
  if (streak >= 1) return '🙂 Dobar početak';
  return '❄️ Počni danas';
}

/* ===================== */
/* COMPONENTS */
/* ===================== */

function StatCard({ title, value, icon, full }) {
  return (
    <View style={[styles.card, full && { width: '100%' }]}>
      <Text style={styles.cardIcon}>{icon}</Text>
      <Text style={styles.cardTitle}>{title}</Text>
      <Text style={styles.cardValue}>{value}</Text>
    </View>
  );
}

function ProgressBar({ value }) {
  const percent = Math.min(value * 100, 100);

  let color = '#22C55E'; // 🟢 green
  if (value >= 0.7 && value < 1) color = '#F59E0B'; // 🟡 orange
  if (value >= 1) color = '#EF4444'; // 🔴 red

  return (
    <View style={styles.progressWrap}>
      <View
        style={[
          styles.progressFill,
          { width: `${percent}%`, backgroundColor: color },
        ]}
      />
    </View>
  );
}

/* ===================== */
/* STYLES */
/* ===================== */

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: '#F7F8FC',
  },
  hello: {
    fontSize: 26,
    fontWeight: '700',
    color: '#1E1E2D',
  },
  sub: {
    color: '#8E8E93',
    marginBottom: 24,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 20,
    width: '48%',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  cardIcon: {
    fontSize: 22,
    marginBottom: 8,
  },
  cardTitle: {
    color: '#8E8E93',
    fontSize: 14,
  },
  cardValue: {
    fontSize: 22,
    fontWeight: '700',
    color: '#1E1E2D',
  },

  /* GOAL */
  goalBox: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  goalTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 10,
    color: '#1E1E2D',
  },
  goalText: {
    marginTop: 8,
    fontWeight: '600',
    color: '#1E1E2D',
  },
  streakHint: {
    marginTop: 6,
    fontSize: 13,
    color: '#8E8E93',
  },
  progressWrap: {
    height: 12,
    backgroundColor: '#E5E7EB',
    borderRadius: 10,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 10,
  },

  /* BUTTONS */
  primaryBtn: {
    backgroundColor: '#5B5BEA',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginTop: 10,
  },
  primaryText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  primaryBtnAlt: {
    backgroundColor: '#1E1E2D',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginTop: 12,
  },
  primaryTextAlt: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  secondaryBtn: {
    borderWidth: 1,
    borderColor: '#5B5BEA',
    padding: 16,
    borderRadius: 16,
    alignItems: 'center',
    marginTop: 12,
  },
  secondaryText: {
    color: '#5B5BEA',
    fontSize: 16,
    fontWeight: '600',
  },
  logout: {
    marginTop: 30,
    alignItems: 'center',
  },
  logoutText: {
    color: '#EF4444',
    fontWeight: '600',
  },
});
