import { View, Text, Button } from 'react-native';
import { useEffect, useState } from 'react';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRouter } from 'expo-router';

export default function BarcodeScannerScreen() {
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const router = useRouter();

  // Ako dozvola još nije proverena
  if (!permission) {
    return <Text>Proveravam dozvolu za kameru...</Text>;
  }

  // Ako dozvola nije data
  if (!permission.granted) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <Text>Potrebna je dozvola za kameru</Text>
        <Button title="Dozvoli kameru" onPress={requestPermission} />
      </View>
    );
  }

  // Normalizacija barkoda (UPC → EAN-13)
  const normalizeBarcode = (code) => {
    let cleaned = code.replace(/\D/g, '');

    if (cleaned.length === 12) {
      cleaned = '0' + cleaned;
    }

    return cleaned;
  };

  const handleBarcodeScanned = ({ data }) => {
    if (scanned) return;

    const barcode = normalizeBarcode(data);
    setScanned(true);

    router.push(`/product?barcode=${barcode}`);
  };

  return (
    <View style={{ flex: 1 }}>
      <CameraView
        style={{ flex: 1 }}
        barcodeScannerSettings={{
          barcodeTypes: ['ean13', 'ean8', 'upc_a', 'upc_e'],
        }}
        onBarcodeScanned={handleBarcodeScanned}
      />

      {scanned && (
        <Button title="Skeniraj ponovo" onPress={() => setScanned(false)} />
      )}
    </View>
  );
}