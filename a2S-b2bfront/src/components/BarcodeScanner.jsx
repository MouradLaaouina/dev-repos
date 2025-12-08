import { useState, useRef, useEffect } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { X, Camera, AlertCircle } from 'lucide-react';

export default function BarcodeScanner({ onScanSuccess, onClose }) {
  const [scanning, setScanning] = useState(false);
  const [error, setError] = useState('');
  const [cameras, setCameras] = useState([]);
  const [selectedCamera, setSelectedCamera] = useState(null);
  const [lastScannedCode, setLastScannedCode] = useState('');
  
  const html5QrCodeRef = useRef(null);
  const lastScanTime = useRef(0);

  useEffect(() => {
    // Récupérer la liste des caméras disponibles
    Html5Qrcode.getCameras().then(devices => {
      if (devices && devices.length) {
        setCameras(devices);
        // Priorité à la caméra arrière (environment)
        const backCamera = devices.find(d => 
          d.label.toLowerCase().includes('back') || 
          d.label.toLowerCase().includes('arrière') ||
          d.label.toLowerCase().includes('environment')
        );
        setSelectedCamera(backCamera ? backCamera.id : devices[0].id);
      } else {
        setError('Aucune caméra détectée sur cet appareil');
      }
    }).catch(err => {
      console.error('Erreur récupération caméras:', err);
      setError('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
    });

    return () => {
      stopScanner();
    };
  }, []);

  const startScanner = async () => {
    if (!selectedCamera) {
      setError('Aucune caméra sélectionnée');
      return;
    }

    try {
      setError('');
      html5QrCodeRef.current = new Html5Qrcode('barcode-reader');
      
      await html5QrCodeRef.current.start(
        selectedCamera,
        {
          fps: 10,
          qrbox: { width: 250, height: 150 },
          aspectRatio: 1.777778
        },
        handleScanSuccess,
        handleScanError
      );
      
      setScanning(true);
    } catch (err) {
      console.error('Erreur démarrage scanner:', err);
      setError('Erreur de démarrage du scanner');
    }
  };

  const handleScanSuccess = (decodedText) => {
    const now = Date.now();
    
    // Debounce: éviter les scans multiples (2 secondes)
    if (decodedText !== lastScannedCode || now - lastScanTime.current > 2000) {
      setLastScannedCode(decodedText);
      lastScanTime.current = now;

      // Feedback visuel
      if (navigator.vibrate) {
        navigator.vibrate(200);
      }
      
      const reader = document.getElementById('barcode-reader');
      if (reader) {
        reader.style.border = '4px solid #10b981';
        setTimeout(() => {
          reader.style.border = '2px solid #10b981';
        }, 300);
      }

      // Callback vers le parent
      onScanSuccess(decodedText);
    }
  };

  const handleScanError = () => {
    // Ignorer les erreurs de scan normales (aucun code détecté)
  };

  const stopScanner = async () => {
    if (html5QrCodeRef.current && scanning) {
      try {
        await html5QrCodeRef.current.stop();
        html5QrCodeRef.current.clear();
        html5QrCodeRef.current = null;
        setScanning(false);
      } catch (err) {
        console.error('Erreur arrêt scanner:', err);
      }
    }
  };

  const switchCamera = async () => {
    await stopScanner();
    
    if (cameras.length > 1) {
      const currentIndex = cameras.findIndex(c => c.id === selectedCamera);
      const nextIndex = (currentIndex + 1) % cameras.length;
      setSelectedCamera(cameras[nextIndex].id);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-xl max-w-md w-full shadow-2xl">
        {/* Header */}
        <div className="bg-green-600 text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Camera className="w-6 h-6" />
            <h2 className="text-xl font-bold">Scanner Code-Barres</h2>
          </div>
          <button
            onClick={onClose}
            className="p-1 hover:bg-green-700 rounded-lg transition"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          {error && (
            <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-2">
              <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
              <span className="text-sm">{error}</span>
            </div>
          )}

          {/* Scanner Container */}
          <div 
            id="barcode-reader"
            className="rounded-lg overflow-hidden mb-4 bg-black"
            style={{
              minHeight: '300px',
              border: '2px solid #10b981',
              transition: 'border 0.3s'
            }}
          />

          {lastScannedCode && (
            <div className="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
              <p className="text-sm font-medium">Dernier code scanné :</p>
              <p className="text-lg font-mono font-bold">{lastScannedCode}</p>
            </div>
          )}

          {/* Buttons */}
          <div className="flex gap-3">
            {!scanning ? (
              <button
                onClick={startScanner}
                disabled={!selectedCamera}
                className="flex-1 bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
              >
                Démarrer le scan
              </button>
            ) : (
              <>
                <button
                  onClick={stopScanner}
                  className="flex-1 bg-orange-600 text-white py-3 rounded-lg font-medium hover:bg-orange-700 transition"
                >
                  Arrêter
                </button>
                {cameras.length > 1 && (
                  <button
                    onClick={switchCamera}
                    className="px-4 bg-gray-600 text-white py-3 rounded-lg font-medium hover:bg-gray-700 transition"
                  >
                    <Camera className="w-5 h-5" />
                  </button>
                )}
              </>
            )}
          </div>

          <p className="text-xs text-gray-500 text-center mt-4">
            Positionnez le code-barres dans le cadre vert
          </p>
        </div>
      </div>
    </div>
  );
}