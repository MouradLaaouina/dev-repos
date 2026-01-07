import { Router } from "express";
import { postWithToken, getWithToken } from "../service/axiosService.js";
import { responseToDTO } from "../service/responseService.js";
import { requireBTOCAccess } from "../middleware/btocAccess.js";

const btocStatsRouter = Router();

// Enregistrer une statistique BToC
btocStatsRouter.post("/", requireBTOCAccess, async (req, res) => {
  const token = req.get("DOLAPIKEY");
  if (!token) {
    return res.status(401).send({ status: "error", message: "Missing DOLAPIKEY" });
  }

  try {
    const payload = req.body || {};
    const response = await postWithToken("/btocstats", payload, token);
    return res.send({ status: "ok", result: responseToDTO(response, null) });
  } catch (error) {
    const status = error?.response?.status || 500;
    const message = error?.response?.data?.message || error?.message || "BtocStats creation failed";
    console.error("BtocStats create error:", message);
    return res.status(status).send({ status: "error", message });
  }
});

// Récupérer les statistiques BToC
btocStatsRouter.get("/", requireBTOCAccess, async (req, res) => {
    const token = req.get("DOLAPIKEY");
    if (!token) {
      return res.status(401).send({ status: "error", message: "Missing DOLAPIKEY" });
    }
  
    try {
      const params = req.query || {};
      const response = await getWithToken("/btocstats", token, params);
      return res.send({ status: "ok", result: responseToDTO(response, null) });
    } catch (error) {
      const status = error?.response?.status || 500;
      const message = error?.response?.data?.message || error?.message || "BtocStats fetch failed";
      console.error("BtocStats fetch error:", message);
      return res.status(status).send({ status: "error", message });
    }
  });

export default btocStatsRouter;
