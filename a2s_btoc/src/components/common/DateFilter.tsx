import React, { useState } from 'react';
import { Calendar, ChevronDown, Filter, RefreshCw } from 'lucide-react';
import Flatpickr from 'react-flatpickr';
import 'flatpickr/dist/themes/light.css';
import { French } from 'flatpickr/dist/l10n/fr';

export type DateRange = {
  startDate: Date | null;
  endDate: Date | null;
  label: string;
};

interface DateFilterProps {
  onFilterChange: (dateRange: DateRange) => void;
  onReset: () => void;
}

const DateFilter: React.FC<DateFilterProps> = ({ onFilterChange, onReset }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedOption, setSelectedOption] = useState<string>('all');
  const [customStartDate, setCustomStartDate] = useState<Date | null>(null);
  const [customEndDate, setCustomEndDate] = useState<Date | null>(null);
  const [activeFilter, setActiveFilter] = useState<DateRange | null>(null);

  const toggleDropdown = () => {
    setIsOpen(!isOpen);
  };

  const handleOptionSelect = (option: string) => {
    setSelectedOption(option);
    let dateRange: DateRange = {
      startDate: null,
      endDate: null,
      label: 'Toutes les dates'
    };

    const now = new Date();
    now.setHours(23, 59, 59, 999); // End of current day
    
    switch (option) {
      case 'today':
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Start of current day
        dateRange = {
          startDate: today,
          endDate: now,
          label: 'Aujourd\'hui'
        };
        break;
      case 'yesterday':
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        yesterday.setHours(0, 0, 0, 0); // Start of yesterday
        const yesterdayEnd = new Date(yesterday);
        yesterdayEnd.setHours(23, 59, 59, 999); // End of yesterday
        dateRange = {
          startDate: yesterday,
          endDate: yesterdayEnd,
          label: 'Hier'
        };
        break;
      case 'last7days':
        const last7Days = new Date();
        last7Days.setDate(last7Days.getDate() - 6); // 7 days including today = today - 6
        last7Days.setHours(0, 0, 0, 0); // Start of 7 days ago
        dateRange = {
          startDate: last7Days,
          endDate: now,
          label: '7 derniers jours'
        };
        break;
      case 'last30days':
        const last30Days = new Date();
        last30Days.setDate(last30Days.getDate() - 29); // 30 days including today = today - 29
        last30Days.setHours(0, 0, 0, 0); // Start of 30 days ago
        dateRange = {
          startDate: last30Days,
          endDate: now,
          label: '30 derniers jours'
        };
        break;
      case 'thisMonth':
        const thisMonth = new Date();
        thisMonth.setDate(1); // First day of current month
        thisMonth.setHours(0, 0, 0, 0); // Start of first day
        dateRange = {
          startDate: thisMonth,
          endDate: now,
          label: 'Ce mois-ci'
        };
        break;
      case 'lastMonth':
        const lastMonth = new Date();
        lastMonth.setMonth(lastMonth.getMonth() - 1); // Previous month
        lastMonth.setDate(1); // First day of previous month
        lastMonth.setHours(0, 0, 0, 0); // Start of first day
        
        const lastMonthEnd = new Date();
        lastMonthEnd.setDate(0); // Last day of previous month
        lastMonthEnd.setHours(23, 59, 59, 999); // End of last day
        
        dateRange = {
          startDate: lastMonth,
          endDate: lastMonthEnd,
          label: 'Mois dernier'
        };
        break;
      case 'thisYear':
        const thisYear = new Date();
        thisYear.setMonth(0, 1); // January 1st of current year
        thisYear.setHours(0, 0, 0, 0); // Start of first day
        dateRange = {
          startDate: thisYear,
          endDate: now,
          label: 'Cette année'
        };
        break;
      case 'lastYear':
        const lastYear = new Date();
        lastYear.setFullYear(lastYear.getFullYear() - 1); // Previous year
        lastYear.setMonth(0, 1); // January 1st of previous year
        lastYear.setHours(0, 0, 0, 0); // Start of first day
        
        const lastYearEnd = new Date();
        lastYearEnd.setFullYear(lastYearEnd.getFullYear() - 1); // Previous year
        lastYearEnd.setMonth(11, 31); // December 31st of previous year
        lastYearEnd.setHours(23, 59, 59, 999); // End of last day
        
        dateRange = {
          startDate: lastYear,
          endDate: lastYearEnd,
          label: 'Année dernière'
        };
        break;
      case 'custom':
        if (customStartDate && customEndDate) {
          // Set time to start of day for start date
          const startDateWithTime = new Date(customStartDate);
          startDateWithTime.setHours(0, 0, 0, 0);
          
          // Set time to end of day for end date
          const endDateWithTime = new Date(customEndDate);
          endDateWithTime.setHours(23, 59, 59, 999);
          
          const formattedStart = customStartDate.toLocaleDateString('fr-FR');
          const formattedEnd = customEndDate.toLocaleDateString('fr-FR');
          
          dateRange = {
            startDate: startDateWithTime,
            endDate: endDateWithTime,
            label: `${formattedStart} - ${formattedEnd}`
          };
        } else {
          // Don't apply filter if custom dates aren't set
          setIsOpen(true);
          return;
        }
        break;
      case 'all':
      default:
        // Reset filter
        dateRange = {
          startDate: null,
          endDate: null,
          label: 'Toutes les dates'
        };
        break;
    }

    console.log('📅 Setting date range:', {
      option,
      startDate: dateRange.startDate?.toISOString(),
      endDate: dateRange.endDate?.toISOString(),
      label: dateRange.label
    });

    setActiveFilter(dateRange);
    onFilterChange(dateRange);
    setIsOpen(false);
  };

  const handleReset = () => {
    setSelectedOption('all');
    setCustomStartDate(null);
    setCustomEndDate(null);
    setActiveFilter(null);
    onReset();
  };

  return (
    <div className="relative">
      <div className="flex flex-col sm:flex-row gap-2">
        <button
          onClick={toggleDropdown}
          className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
        >
          <Calendar className="h-4 w-4" />
          <span>
            {activeFilter ? activeFilter.label : 'Filtrer par date'}
          </span>
          <ChevronDown className="h-4 w-4" />
        </button>

        {activeFilter && (
          <button
            onClick={handleReset}
            className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
          >
            <RefreshCw className="h-4 w-4" />
            Réinitialiser
          </button>
        )}
      </div>

      {isOpen && (
        <div className="absolute z-10 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 p-4 animate-fade-in">
          <h3 className="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
            <Filter className="h-4 w-4" />
            Filtrer par période
          </h3>
          
          <div className="space-y-2">
            <button
              onClick={() => handleOptionSelect('today')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'today' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Aujourd'hui
            </button>
            <button
              onClick={() => handleOptionSelect('yesterday')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'yesterday' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Hier
            </button>
            <button
              onClick={() => handleOptionSelect('last7days')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'last7days' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              7 derniers jours
            </button>
            <button
              onClick={() => handleOptionSelect('last30days')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'last30days' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              30 derniers jours
            </button>
            <button
              onClick={() => handleOptionSelect('thisMonth')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'thisMonth' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Ce mois-ci
            </button>
            <button
              onClick={() => handleOptionSelect('lastMonth')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'lastMonth' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Mois dernier
            </button>
            <button
              onClick={() => handleOptionSelect('thisYear')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'thisYear' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Cette année
            </button>
            <button
              onClick={() => handleOptionSelect('lastYear')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'lastYear' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Année dernière
            </button>
            <button
              onClick={() => handleOptionSelect('all')}
              className={`w-full text-left px-3 py-2 rounded-md text-sm ${
                selectedOption === 'all' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
              }`}
            >
              Toutes les dates
            </button>
            
            <div className={`mt-3 pt-3 border-t border-gray-200 ${
              selectedOption === 'custom' ? 'bg-primary-50 p-3 rounded-md' : ''
            }`}>
              <button
                onClick={() => setSelectedOption('custom')}
                className={`w-full text-left px-3 py-2 rounded-md text-sm font-medium ${
                  selectedOption === 'custom' ? 'bg-primary-100 text-primary-800' : 'hover:bg-gray-100'
                }`}
              >
                Période personnalisée
              </button>
              
              {selectedOption === 'custom' && (
                <div className="mt-3 space-y-3">
                  <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">
                      Date de début
                    </label>
                    <Flatpickr
                      value={customStartDate || undefined}
                      onChange={([date]) => setCustomStartDate(date)}
                      options={{
                        locale: French,
                        dateFormat: "d/m/Y",
                        maxDate: customEndDate || undefined
                      }}
                      className="input w-full text-sm"
                      placeholder="Sélectionner date"
                    />
                  </div>
                  
                  <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">
                      Date de fin
                    </label>
                    <Flatpickr
                      value={customEndDate || undefined}
                      onChange={([date]) => setCustomEndDate(date)}
                      options={{
                        locale: French,
                        dateFormat: "d/m/Y",
                        minDate: customStartDate || undefined
                      }}
                      className="input w-full text-sm"
                      placeholder="Sélectionner date"
                    />
                  </div>
                  
                  <button
                    onClick={() => handleOptionSelect('custom')}
                    disabled={!customStartDate || !customEndDate}
                    className="btn btn-primary w-full text-sm py-1"
                  >
                    Appliquer
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DateFilter;