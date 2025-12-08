import { Router } from "express";
import { getWithToken, postWithToken } from "../service/axiosService.js";
import { responseToDTO } from "../service/responseService.js";

const shipmentRouter = Router();

// Liste des livraisons
shipmentRouter.get("/", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const pageNbr = req.query.page || 0;
  const thirdparty_ids = req.query.thirdparty_ids;
  const status = req.query.status; // 0=draft, 1=validated, 2=closed

  try {
    let url = `/shipments?sortfield=t.rowid&sortorder=DESC&limit=100&page=${parseInt(pageNbr)}`;
    if (thirdparty_ids) url += `&thirdparty_ids=${thirdparty_ids}`;
    if (status !== undefined) url += `&sqlfilters=(t.fk_statut:=:${status})`;

    const shipments = await getWithToken(url, token);

    // Get clients to map socid to client_name
    let clientsMap = {};
    try {
      const clients = await getWithToken(`/salesrepclients/salesreps/clients?client_type=1`, token);
      clients.forEach(c => {
        clientsMap[c.id] = c.name || c.nom;
      });
    } catch (err) {
      console.error('Error fetching clients:', err.message);
    }

    // Enrich shipments with client name and formatted data
    const enrichedShipments = shipments.map((shipment) => {
      return {
        ...shipment,
        client_name: clientsMap[shipment.socid] || null,
        status_label: getStatusLabel(shipment.statut || shipment.fk_statut),
        date_creation_formatted: shipment.date_creation
          ? new Date(shipment.date_creation * 1000).toLocaleDateString('fr-FR')
          : null,
        date_delivery_formatted: shipment.date_delivery
          ? new Date(shipment.date_delivery * 1000).toLocaleDateString('fr-FR')
          : null
      };
    });

    res.send({ status: "ok", result: responseToDTO(enrichedShipments, null) });
  } catch (error) {
    console.error('Shipments list error:', error);
    res.status(500).send({ status: "error", message: error.message });
  }
});

// Détail d'une livraison
shipmentRouter.get("/:id", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const id = req.params.id;

  try {
    const shipment = await getWithToken(`/shipments/${id}`, token);

    // Get client info
    let clientInfo = null;
    if (shipment.socid) {
      try {
        clientInfo = await getWithToken(`/thirdparties/${shipment.socid}`, token);
      } catch (err) {
        console.error('Error fetching client:', err.message);
      }
    }

    // Get origin order/proposal info if available
    let originInfo = null;
    if (shipment.origin_id && shipment.origin_type === 'commande') {
      try {
        originInfo = await getWithToken(`/orders/${shipment.origin_id}`, token);
      } catch (err) {
        console.error('Error fetching origin order:', err.message);
      }
    }

    const enrichedShipment = {
      ...shipment,
      client_name: clientInfo?.name || clientInfo?.nom || null,
      client_address: clientInfo?.address || null,
      client_zip: clientInfo?.zip || null,
      client_town: clientInfo?.town || null,
      client_phone: clientInfo?.phone || null,
      client_email: clientInfo?.email || null,
      status_label: getStatusLabel(shipment.statut || shipment.fk_statut),
      date_creation_formatted: shipment.date_creation
        ? new Date(shipment.date_creation * 1000).toLocaleDateString('fr-FR')
        : null,
      date_delivery_formatted: shipment.date_delivery
        ? new Date(shipment.date_delivery * 1000).toLocaleDateString('fr-FR')
        : null,
      date_valid_formatted: shipment.date_valid
        ? new Date(shipment.date_valid * 1000).toLocaleDateString('fr-FR')
        : null,
      origin_ref: originInfo?.ref || null,
      origin_id: shipment.origin_id
    };

    res.send({ status: "ok", result: responseToDTO(enrichedShipment, null) });
  } catch (error) {
    console.error('Shipment detail error:', error);
    res.status(500).send({ status: "error", message: error.message });
  }
});

// Valider une livraison (signature)
shipmentRouter.post("/:id/validate", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const id = req.params.id;
  const body = req.body || { notrigger: 0 };

  try {
    const response = await postWithToken(`/shipments/${id}/validate`, body, token);
    res.send({ status: "ok", result: response });
  } catch (error) {
    console.error('Shipment validate error:', error);
    res.status(500).send({ status: "error", message: error.message });
  }
});

// Clôturer une livraison (marquer comme livrée)
shipmentRouter.post("/:id/close", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const id = req.params.id;
  const body = req.body || { notrigger: 0 };

  try {
    const response = await postWithToken(`/shipments/${id}/close`, body, token);
    res.send({ status: "ok", result: response });
  } catch (error) {
    console.error('Shipment close error:', error);
    res.status(500).send({ status: "error", message: error.message });
  }
});

// Helper function pour les labels de statut
function getStatusLabel(status) {
  const statusNum = Number(status);
  switch (statusNum) {
    case 0: return 'Brouillon';
    case 1: return 'Validé';
    case 2: return 'Livré';
    default: return 'Inconnu';
  }
}

export default shipmentRouter;
