import { useState, useEffect } from 'react';
import { Search, ChevronLeft, X, Loader2, ArrowLeft } from 'lucide-react';
import { clientService } from '../../services';

export default function SelloutClientPage({ user, onClientSelect, onBack, onLogout }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [clients, setClients] = useState([]);
  const [filteredClients, setFilteredClients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchClients = async () => {
      try {
        setLoading(true);
        const data = await clientService.getClients({ page: 0 });
        setClients(data);
        setFilteredClients(data);
      } catch (err) {
        console.error('Erreur chargement clients:', err);
        setError('Impossible de charger les clients');
      } finally {
        setLoading(false);
      }
    };

    fetchClients();
  }, []);

  useEffect(() => {
    if (!searchTerm) {
      setFilteredClients(clients);
      return;
    }

    const term = searchTerm.toLowerCase();
    const filtered = clients.filter(client =>
      client.name?.toLowerCase().includes(term) ||
      client.nom?.toLowerCase().includes(term) ||
      client.code_client?.toLowerCase().includes(term) ||
      client.email?.toLowerCase().includes(term) ||
      client.town?.toLowerCase().includes(term)
    );
    setFilteredClients(filtered);
  }, [searchTerm, clients]);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-blue-600 text-white px-6 py-4 shadow-lg">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <button
              onClick={onBack}
              className="flex items-center gap-2 bg-blue-700 px-3 py-2 rounded-lg hover:bg-blue-800 transition-colors"
            >
              <ArrowLeft className="w-5 h-5" />
              <span className="text-sm font-medium">Retour</span>
            </button>
            <h1 className="text-xl font-bold">Sellout - Client</h1>
          </div>
          <button
            onClick={onLogout}
            className="text-sm bg-blue-700 px-4 py-2 rounded-lg hover:bg-blue-800"
          >
            Déconnexion
          </button>
        </div>
        <div className="flex items-center bg-white rounded-xl px-4 py-3">
          <Search className="w-5 h-5 text-gray-400 mr-3" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Rechercher un client..."
            className="flex-1 text-gray-900 focus:outline-none"
          />
          {searchTerm && (
            <button onClick={() => setSearchTerm('')}>
              <X className="w-5 h-5 text-gray-400" />
            </button>
          )}
        </div>
      </div>

      <div className="px-6 py-4">
        <p className="text-sm text-gray-600 mb-4">
          Connecté en tant que: <span className="font-medium text-gray-900">{user.login || user.email}</span>
        </p>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
            {error}
          </div>
        )}

        {loading ? (
          <div className="flex flex-col items-center justify-center py-12">
            <Loader2 className="w-12 h-12 text-blue-600 animate-spin mb-4" />
            <p className="text-gray-500">Chargement des clients...</p>
          </div>
        ) : (
          <div className="space-y-3">
            {filteredClients.length > 0 ? (
              filteredClients.map(client => (
                <button
                  key={client.id}
                  onClick={() => onClientSelect(client)}
                  className="w-full bg-white rounded-xl p-4 shadow-sm border border-gray-200 hover:shadow-md hover:border-blue-300 transition-all text-left"
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-mono bg-blue-100 text-blue-700 px-2 py-1 rounded">
                          {client.code_client || client.id}
                        </span>
                      </div>
                      <h3 className="font-semibold text-gray-900 mb-1">
                        {client.name || client.nom || 'Client sans nom'}
                      </h3>
                      {client.email && (
                        <p className="text-sm text-gray-600">{client.email}</p>
                      )}
                      {client.town && (
                        <p className="text-xs text-gray-500 mt-1">{client.town}</p>
                      )}
                    </div>
                    <ChevronLeft className="w-5 h-5 text-gray-400 transform rotate-180" />
                  </div>
                </button>
              ))
            ) : (
              <div className="text-center py-12">
                <Search className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <p className="text-gray-500">
                  {searchTerm ? 'Aucun client trouvé' : 'Aucun client disponible'}
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
