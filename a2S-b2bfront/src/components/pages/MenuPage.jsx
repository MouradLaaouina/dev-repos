import { Search, FileText, User, Scan, Truck } from 'lucide-react';

export default function MenuPage({ user, onNavigate, onLogout }) {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-green-600 text-white px-6 py-4 shadow-lg">
        <div className="flex items-center justify-between mb-2">
          <h1 className="text-xl font-bold">Alliance Synergie Santé</h1>
          <button
            onClick={onLogout}
            className="text-sm bg-green-700 px-4 py-2 rounded-lg hover:bg-green-800 transition"
          >
            Déconnexion
          </button>
        </div>
        <div className="flex items-center gap-2 text-green-100 text-sm">
          <User className="w-4 h-4" />
          <span>{user.login || user.email}</span>
        </div>
      </div>

      {/* Menu Cards */}
      <div className="px-6 py-8">
        <h2 className="text-2xl font-bold text-gray-900 mb-6">
          Que souhaitez-vous faire ?
        </h2>

        <div className="space-y-4">
          {/* SellIn (flux existant) */}
          <button
            onClick={() => onNavigate('clientSearch')}
            className="w-full bg-white rounded-xl p-6 shadow-sm border-2 border-gray-200 hover:border-green-500 hover:shadow-md transition-all text-left group"
          >
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-600 transition">
                <Search className="w-8 h-8 text-green-600 group-hover:text-white transition" />
              </div>
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 mb-1">
                  SellIn
                </h3>
                <p className="text-sm text-gray-600">
                  Rechercher un client et créer un devis (flux existant)
                </p>
              </div>
              <div className="text-gray-400 group-hover:text-green-600 transition">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </button>

          {/* Sellout */}
          <button
            onClick={() => onNavigate('selloutClientSearch')}
            className="w-full bg-white rounded-xl p-6 shadow-sm border-2 border-gray-200 hover:border-blue-500 hover:shadow-md transition-all text-left group"
          >
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-600 transition">
                <Scan className="w-8 h-8 text-blue-600 group-hover:text-white transition" />
              </div>
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 mb-1">
                  Sellout
                </h3>
                <p className="text-sm text-gray-600">
                  Vendre sur le terrain et envoyer vers Dolibarr (module Sellout)
                </p>
              </div>
              <div className="text-gray-400 group-hover:text-blue-600 transition">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </button>

          {/* Mes devis */}
          <button
            onClick={() => onNavigate('proposalList')}
            className="w-full bg-white rounded-xl p-6 shadow-sm border-2 border-gray-200 hover:border-blue-500 hover:shadow-md transition-all text-left group"
          >
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-600 transition">
                <FileText className="w-8 h-8 text-blue-600 group-hover:text-white transition" />
              </div>
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 mb-1">
                  Mes Devis
                </h3>
                <p className="text-sm text-gray-600">
                  Consulter les devis en attente et validés
                </p>
              </div>
              <div className="text-gray-400 group-hover:text-blue-600 transition">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </button>

          {/* Mes Livraisons */}
          <button
            onClick={() => onNavigate('shipmentList')}
            className="w-full bg-white rounded-xl p-6 shadow-sm border-2 border-gray-200 hover:border-purple-500 hover:shadow-md transition-all text-left group"
          >
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-600 transition">
                <Truck className="w-8 h-8 text-purple-600 group-hover:text-white transition" />
              </div>
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 mb-1">
                  Mes Livraisons
                </h3>
                <p className="text-sm text-gray-600">
                  Consulter et signer les bons de livraison
                </p>
              </div>
              <div className="text-gray-400 group-hover:text-purple-600 transition">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>
  );
}
