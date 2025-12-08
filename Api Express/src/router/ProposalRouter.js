import { Router } from "express";
import { getWithToken, postWithToken } from "../service/axiosService.js";
import { responseToDTO } from "../service/responseService.js";

const proposalRouter = Router();

proposalRouter.get("/", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const pageNbr = req.query.page || 0;
  const thirdparty_ids = req.query.thirdparty_ids;

  try {
    // Use custom endpoint that returns proposals with linked orders and shipments
    let url = `/salesrepclients/proposals/with-orders?status=all&limit=100&page=${parseInt(pageNbr)}`;
    if (thirdparty_ids) url += `&thirdparty_ids=${thirdparty_ids}`;

    const proposals = await getWithToken(url, token);

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

    // Enrich proposals with shipment status and modification info
    const enrichedProposals = proposals.map((proposal) => {
      let enriched = { ...proposal };

      // Map client_name from socid
      enriched.client_name = clientsMap[proposal.socid] || null;

      // Map created_at from datec (Unix timestamp to ISO string)
      if (proposal.datec) {
        enriched.created_at = new Date(proposal.datec * 1000).toISOString();
      }

      // Get shipment status from linked_orders
      if (proposal.linked_orders && proposal.linked_orders.length > 0) {
        const order = proposal.linked_orders[0];
        if (order.shipments && order.shipments.length > 0) {
          const shipment = order.shipments[0];
          enriched.shipment_status = Number(shipment.status);
          enriched.shipment = {
            statut: Number(shipment.status),
            ref: shipment.ref,
            date_delivery: shipment.date_delivery,
            tracking_number: shipment.tracking_number,
            shipping_method: shipment.shipping_method,
            author: shipment.author,
            validated_by: shipment.validated_by
          };
        }
      }

      // Check if proposal was modified by someone other than creator
      const propStatus = Number(proposal.statut || proposal.status);
      if (propStatus < 2 && proposal.last_modified_by &&
          String(proposal.last_modified_by) !== String(proposal.user_author_id)) {
        enriched.modified_by_other = true;
      }

      return enriched;
    });

    res.send({ status: "ok", result: responseToDTO(enrichedProposals, null) });
  } catch (error) {
    res.status(500).send({ status: "error", message: error.message });
  }
});

proposalRouter.get("/:id", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const id = req.params.id;

  try {
    // Use custom endpoint to get proposal with linked orders/shipments
    const proposals = await getWithToken(`/salesrepclients/proposals/with-orders?status=all&limit=100`, token);
    const proposal = proposals.find(p => String(p.id) === String(id));

    if (!proposal) {
      return res.status(404).send({ status: "error", message: "Proposal not found" });
    }

    let enrichedProposal = { ...proposal };

    // Get client name
    try {
      const client = await getWithToken(`/thirdparties/${proposal.socid}`, token);
      enrichedProposal.client_name = client.name || client.nom;
    } catch (err) {
      console.error('Error fetching client:', err.message);
    }

    // Map created_at from datec
    if (proposal.datec) {
      enrichedProposal.created_at = new Date(proposal.datec * 1000).toISOString();
    }

    // Get shipment information from linked_orders
    if (proposal.linked_orders && proposal.linked_orders.length > 0) {
      const order = proposal.linked_orders[0];
      if (order.shipments && order.shipments.length > 0) {
        const shipment = order.shipments[0];
        enrichedProposal.shipment = {
          statut: Number(shipment.status),
          ref: shipment.ref,
          date_delivery: shipment.date_delivery,
          tracking_number: shipment.tracking_number,
          shipping_method: shipment.shipping_method,
          author: shipment.author,
          validated_by: shipment.validated_by
        };
      }
    }

    // Check if proposal was modified by someone other than creator
    const propStatus = Number(proposal.statut || proposal.status);
    if (propStatus < 2 && proposal.last_modified_by &&
        String(proposal.last_modified_by) !== String(proposal.user_author_id)) {
      enrichedProposal.modified_by_other = true;
    }

    res.send({ status: "ok", result: responseToDTO(enrichedProposal, null) });
  } catch (error) {
    res.status(500).send({ status: "error", message: error.message });
  }
});

proposalRouter.post("/", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  const { socid, note_private, mode_reglement_id, lines } = req.body;

  try {
    // Map lines to Dolibarr format with fk_product
    const mappedLines = lines.map(line => ({
      fk_product: line.product_id || line.fk_product,
      product_type: line.product_type || 0,
      qty: line.qty,
      subprice: line.subprice,
      tva_tx: line.tva_tx || 20,
      desc: line.label || line.desc || '',
      remise_percent: line.remise_percent || 0
    }));

    const body = {
      socid,
      date: new Date().toISOString().substring(0, 10),
      mode_reglement_id,
      note_private,
      lines: mappedLines
    };

    const response = await postWithToken("/proposals", body, token);
    res.send({ status: "ok", result: response });
  } catch (error) {
    res.status(500).send({ status: "error", message: error.message });
  }
});

export default proposalRouter;
