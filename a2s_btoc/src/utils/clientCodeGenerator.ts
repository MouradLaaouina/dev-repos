/**
 * Generates the next available client code
 * In Dolibarr migration, this is usually handled by the ERP itself.
 * This is now a client-side placeholder.
 */
export const generateNextClientCode = async (): Promise<string> => {
  const fallbackCode = Math.floor(100000 + Math.random() * 900000).toString();
  console.log(`⚠️ Generated client code: ${fallbackCode}`);
  return fallbackCode;
};
